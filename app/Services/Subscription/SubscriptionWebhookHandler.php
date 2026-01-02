<?php

namespace App\Services\Subscription;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\AuditLog;
use App\Services\Invoice\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhookHandler
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle invoice.paid event.
     * This is triggered when a subscription invoice is successfully paid.
     */
    public function handleInvoicePaid(\Stripe\Invoice $stripeInvoice): void
    {
        // Skip non-subscription invoices
        if (!$stripeInvoice->subscription) {
            Log::info('Skipping non-subscription invoice', ['invoice_id' => $stripeInvoice->id]);
            return;
        }

        $subscription = UserSubscription::where('stripe_subscription_id', $stripeInvoice->subscription)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for invoice.paid', [
                'stripe_subscription_id' => $stripeInvoice->subscription,
                'invoice_id' => $stripeInvoice->id,
            ]);
            return;
        }

        DB::transaction(function () use ($subscription, $stripeInvoice) {
            // Check if invoice already processed
            $existingInvoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
            if ($existingInvoice && $existingInvoice->status === 'paid') {
                Log::info('Invoice already processed', ['stripe_invoice_id' => $stripeInvoice->id]);
                return;
            }

            // Create or update local invoice
            $invoice = $this->createOrUpdateInvoice($subscription, $stripeInvoice, 'paid');

            // Update subscription status
            $isFirstPayment = $subscription->status === 'pending';

            $subscription->update([
                'status' => UserSubscription::STATUS_ACTIVE,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeInvoice->period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeInvoice->period_end),
                'cancel_at_period_end' => false,
            ]);

            // Reset bag allocations on renewal
            if (!$isFirstPayment) {
                $this->resetBagAllocations($subscription);
            }

            // Apply pending plan change if exists
            if ($subscription->pending_plan_id) {
                $this->applyPendingPlanChange($subscription);
            }

            // Set subscription on user if not already set
            if ($subscription->user->subscription_id !== $subscription->id) {
                $subscription->user->update(['subscription_id' => $subscription->id]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => $isFirstPayment ? 'subscription_activated' : 'subscription_renewed',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'stripe_invoice_id' => $stripeInvoice->id,
                    'amount' => $stripeInvoice->amount_paid / 100,
                    'period_start' => date('Y-m-d', $stripeInvoice->period_start),
                    'period_end' => date('Y-m-d', $stripeInvoice->period_end),
                ],
            ]);

            Log::info('Processed invoice.paid for subscription', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
            ]);
        });
    }

    /**
     * Handle invoice.payment_failed event.
     */
    public function handleInvoicePaymentFailed(\Stripe\Invoice $stripeInvoice): void
    {
        if (!$stripeInvoice->subscription) {
            return;
        }

        $subscription = UserSubscription::where('stripe_subscription_id', $stripeInvoice->subscription)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for invoice.payment_failed', [
                'stripe_subscription_id' => $stripeInvoice->subscription,
            ]);
            return;
        }

        DB::transaction(function () use ($subscription, $stripeInvoice) {
            // Create invoice record
            $this->createOrUpdateInvoice($subscription, $stripeInvoice, 'payment_failed');

            // Update subscription status
            $subscription->update([
                'status' => UserSubscription::STATUS_PAST_DUE,
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_payment_failed',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'stripe_invoice_id' => $stripeInvoice->id,
                    'amount' => $stripeInvoice->amount_due / 100,
                ],
            ]);

            Log::warning('Subscription payment failed', [
                'subscription_id' => $subscription->id,
                'stripe_invoice_id' => $stripeInvoice->id,
            ]);
        });
    }

    /**
     * Handle customer.subscription.updated event.
     */
    public function handleSubscriptionUpdated(\Stripe\Subscription $stripeSubscription): void
    {
        $subscription = UserSubscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for update', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
            return;
        }

        DB::transaction(function () use ($subscription, $stripeSubscription) {
            $updates = [
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
            ];

            // Map Stripe status to local status
            $statusMap = [
                'active' => UserSubscription::STATUS_ACTIVE,
                'past_due' => UserSubscription::STATUS_PAST_DUE,
                'canceled' => UserSubscription::STATUS_CANCELLED,
                'paused' => UserSubscription::STATUS_PAUSED,
            ];

            if (isset($statusMap[$stripeSubscription->status])) {
                $updates['status'] = $statusMap[$stripeSubscription->status];
            }

            $subscription->update($updates);

            Log::info('Subscription updated from webhook', [
                'subscription_id' => $subscription->id,
                'stripe_status' => $stripeSubscription->status,
            ]);
        });
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    public function handleSubscriptionDeleted(\Stripe\Subscription $stripeSubscription): void
    {
        $subscription = UserSubscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for deletion', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
            return;
        }

        DB::transaction(function () use ($subscription, $stripeSubscription) {
            $subscription->update([
                'status' => UserSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            // Remove subscription from user
            if ($subscription->user->subscription_id === $subscription->id) {
                $subscription->user->update(['subscription_id' => null]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $subscription->user_id,
                'action' => 'subscription_cancelled',
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'metadata' => [
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'ended_at' => date('Y-m-d H:i:s'),
                ],
            ]);

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
            ]);
        });
    }

    /**
     * Create or update local invoice from Stripe invoice.
     */
    protected function createOrUpdateInvoice(
        UserSubscription $subscription,
        \Stripe\Invoice $stripeInvoice,
        string $status
    ): Invoice {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();

        if ($invoice) {
            $invoice->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? now() : null,
            ]);
            return $invoice;
        }

        // Create new invoice
        $invoice = Invoice::create([
            'stripe_invoice_id' => $stripeInvoice->id,
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'type' => 'subscription',
            'status' => $status,
            'currency' => strtoupper($stripeInvoice->currency),
            'subtotal' => $stripeInvoice->subtotal / 100,
            'tax' => $stripeInvoice->tax ? $stripeInvoice->tax / 100 : 0,
            'total' => $stripeInvoice->total / 100,
            'issued_at' => \Carbon\Carbon::createFromTimestamp($stripeInvoice->created),
            'paid_at' => $status === 'paid' ? now() : null,
            'metadata' => [
                'stripe_invoice_number' => $stripeInvoice->number,
                'billing_reason' => $stripeInvoice->billing_reason,
            ],
        ]);

        // Create invoice line items
        foreach ($stripeInvoice->lines->data as $line) {
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'type' => 'subscription',
                'description' => $line->description ?? 'Subscription charge',
                'quantity' => $line->quantity ?? 1,
                'unit_price' => $line->unit_amount ? $line->unit_amount / 100 : $line->amount / 100,
                'amount' => $line->amount / 100,
            ]);
        }

        return $invoice;
    }

    /**
     * Reset bag allocations for new billing period with banking logic.
     */
    protected function resetBagAllocations(UserSubscription $subscription): void
    {
        $plan = $subscription->plan;
        $bagsForCycle = $plan->getBagsForCycle($subscription->billing_cycle);

        // Banking Logic: unused bags roll over
        // Calculate unused bags from previous period
        $unusedBags = max(0, $subscription->bags_plan_balance);

        // Update new balance = new allocation + unused bags
        $newBalance = $bagsForCycle + $unusedBags;

        // Update available bags = new balance
        // Note: bags_plan_total remains strictly the plan's base amount for reference
        // bags_plan_balance effectively becomes the "banked + new" total

        $subscription->update([
            'bags_plan_total' => $bagsForCycle,
            'bags_plan_balance' => $newBalance,
            'bags_plan_used' => 0,
            'bags_available' => $newBalance,
        ]);

        Log::info("Reset bag allocations with banking", [
            'subscription_id' => $subscription->id,
            'rolled_over' => $unusedBags,
            'new_balance' => $newBalance
        ]);
    }

    /**
     * Apply pending plan change after renewal.
     */
    protected function applyPendingPlanChange(UserSubscription $subscription): void
    {
        $newPlan = SubscriptionPlan::find($subscription->pending_plan_id);

        if (!$newPlan) {
            $subscription->update([
                'pending_plan_id' => null,
                'pending_billing_cycle' => null,
            ]);
            return;
        }

        $newCycle = $subscription->pending_billing_cycle ?? $subscription->billing_cycle;
        $bagsForCycle = $newPlan->getBagsForCycle($newCycle);

        $subscription->update([
            'plan_id' => $newPlan->id,
            'billing_cycle' => $newCycle,
            'bags_plan_total' => $bagsForCycle,
            'bags_plan_balance' => $bagsForCycle,
            'bags_plan_used' => 0,
            'bags_available' => $bagsForCycle,
            'pending_plan_id' => null,
            'pending_billing_cycle' => null,
            'stripe_schedule_id' => null, // Clear schedule after application
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $subscription->user_id,
            'action' => 'subscription_plan_changed',
            'entity_type' => 'subscription',
            'entity_id' => $subscription->id,
            'metadata' => [
                'new_plan_id' => $newPlan->id,
                'new_plan_name' => $newPlan->name,
                'billing_cycle' => $newCycle,
            ],
        ]);
    }

    /**
     * Handle invoice.finalized event.
     * Creates pending invoice record before payment is attempted.
     */
    public function handleInvoiceFinalized(\Stripe\Invoice $stripeInvoice): void
    {
        // Skip non-subscription invoices
        if (!$stripeInvoice->subscription) {
            Log::info('Skipping non-subscription invoice finalized', ['invoice_id' => $stripeInvoice->id]);
            return;
        }

        $subscription = UserSubscription::where('stripe_subscription_id', $stripeInvoice->subscription)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for invoice.finalized', [
                'stripe_subscription_id' => $stripeInvoice->subscription,
                'invoice_id' => $stripeInvoice->id,
            ]);
            return;
        }

        // Check if invoice already exists
        $existingInvoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
        if ($existingInvoice) {
            Log::info('Invoice already exists for finalized event', ['stripe_invoice_id' => $stripeInvoice->id]);
            return;
        }

        // Create pending invoice
        $this->createOrUpdateInvoice($subscription, $stripeInvoice, 'pending_payment');

        Log::info('Created pending invoice from finalized event', [
            'stripe_invoice_id' => $stripeInvoice->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Handle charge.refunded event for subscription invoices.
     * Updates subscription invoice status for partial/full refunds.
     */
    public function handleChargeRefunded(\Stripe\Charge $charge): void
    {
        // Get the invoice from payment intent
        $paymentIntentId = $charge->payment_intent;
        if (!$paymentIntentId) {
            return;
        }

        // Find subscription invoice that was paid with this charge
        $invoice = Invoice::where('type', 'subscription')
            ->whereHas('payments', function ($query) use ($paymentIntentId) {
                $query->where('stripe_payment_intent_id', $paymentIntentId);
            })
            ->first();

        if (!$invoice) {
            // Not a subscription invoice, PPO handler will deal with it
            return;
        }

        DB::transaction(function () use ($invoice, $charge) {
            $amountRefunded = $charge->amount_refunded / 100;
            $totalAmount = $charge->amount / 100;
            $isFullRefund = $amountRefunded >= $totalAmount;

            // Update invoice status
            $newStatus = $isFullRefund ? 'refunded' : 'partially_refunded';
            $invoice->update([
                'status' => $newStatus,
                'refunded_amount' => $amountRefunded,
            ]);

            // Log audit
            AuditLog::create([
                'user_id' => $invoice->user_id,
                'action' => $isFullRefund ? 'subscription_invoice_refunded' : 'subscription_invoice_partial_refund',
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'metadata' => [
                    'charge_id' => $charge->id,
                    'amount_refunded' => $amountRefunded,
                    'total_amount' => $totalAmount,
                ],
            ]);

            Log::info('Processed subscription refund', [
                'invoice_id' => $invoice->id,
                'status' => $newStatus,
                'amount_refunded' => $amountRefunded,
            ]);
        });
    }
}

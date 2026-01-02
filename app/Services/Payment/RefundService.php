<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Stripe\StripeService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    protected StripeService $stripe;
    protected InvoiceService $invoices;

    public function __construct(StripeService $stripe, InvoiceService $invoices)
    {
        $this->stripe = $stripe;
        $this->invoices = $invoices;
    }

    /**
     * Process refund for an invoice (admin-triggered).
     */
    public function processRefund(Invoice $invoice, string $reason, ?User $admin = null): Payment
    {
        // Find successful payment
        $payment = Payment::where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->first();

        if (!$payment) {
            throw new \DomainException('No successful payment found for this invoice');
        }

        if (!$payment->stripe_payment_intent_id) {
            throw new \DomainException('Payment has no Stripe payment intent');
        }

        // Guard: Prevent double refund
        if ($payment->isRefunded()) {
            throw new \DomainException('Payment has already been refunded');
        }

        return DB::transaction(function () use ($invoice, $payment, $reason, $admin) {
            // Issue Stripe refund
            $refund = $this->stripe->refundPayment($payment);

            // Update payment
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'stripe_refund_id' => $refund->id,
                'refunded_at' => now(),
            ]);

            // Update invoice
            $this->invoices->markRefunded($invoice, $reason);

            // Create audit log
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => 'invoice_refund',
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'metadata' => [
                    'reason' => $reason,
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $refund->id,
                    'amount' => $payment->amount,
                ],
            ]);

            Log::info('Refund processed: ' . $refund->id . ' for invoice: ' . $invoice->id);

            return $payment->fresh();
        });
    }

    /**
     * Process partial refund for an invoice.
     */
    public function processPartialRefund(Invoice $invoice, float $amountToRefund, string $reason, ?User $admin = null): Payment
    {
        $payment = Payment::where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->first();

        if (!$payment) {
            throw new \DomainException('No successful payment found for this invoice');
        }

        if ($amountToRefund > $payment->amount) {
            throw new \DomainException('Refund amount exceeds payment amount');
        }

        $amountCents = (int) round($amountToRefund * 100);

        return DB::transaction(function () use ($invoice, $payment, $amountCents, $amountToRefund, $reason, $admin) {
            // Issue partial Stripe refund
            $refund = $this->stripe->refundPayment($payment, $amountCents);

            // Update payment
            $payment->update([
                'status' => Payment::STATUS_PARTIALLY_REFUNDED,
                'stripe_refund_id' => $refund->id,
                'refunded_at' => now(),
            ]);

            // Update invoice
            $this->invoices->markPartiallyRefunded($invoice, $amountToRefund);

            // Create audit log
            AuditLog::create([
                'user_id' => $admin?->id,
                'action' => 'invoice_partial_refund',
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'metadata' => [
                    'reason' => $reason,
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $refund->id,
                    'amount_refunded' => $amountToRefund,
                    'original_amount' => $payment->amount,
                ],
            ]);

            Log::info('Partial refund processed: ' . $refund->id . ' for invoice: ' . $invoice->id);

            return $payment->fresh();
        });
    }

    /**
     * Handle refund confirmation from Stripe webhook (charge.refunded).
     * This is for reconciliation - refund may have been initiated elsewhere.
     */
    public function handleRefundFromWebhook(string $paymentIntentId, string $refundId, int $amountRefunded): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning('Payment not found for refund webhook: ' . $paymentIntentId);
            return;
        }

        // Idempotency: skip if already refunded
        if ($payment->isRefunded()) {
            Log::info('Payment already refunded: ' . $paymentIntentId);
            return;
        }

        DB::transaction(function () use ($payment, $refundId, $amountRefunded) {
            // GUARD: Double check not already refunded
            if ($payment->status === Payment::STATUS_REFUNDED) {
                Log::info('Payment already refunded (inside transaction): ' . $payment->id);
                return;
            }

            $amountInDollars = $amountRefunded / 100;
            $isFullRefund = $amountInDollars >= $payment->amount;

            // Update payment
            $payment->update([
                'status' => $isFullRefund ? Payment::STATUS_REFUNDED : Payment::STATUS_PARTIALLY_REFUNDED,
                'stripe_refund_id' => $refundId,
                'refunded_at' => now(),
            ]);

            // Update invoice status based on refund type
            $invoice = $payment->invoice;
            if ($invoice && $invoice->status === 'paid') {
                if ($isFullRefund) {
                    $this->invoices->markRefunded($invoice, 'Refund confirmed via Stripe webhook');
                } else {
                    $this->invoices->markPartiallyRefunded($invoice, $amountInDollars);
                }
            }

            // Audit log for webhook refund
            AuditLog::create([
                'user_id' => null, // System/webhook action
                'action' => 'refund_webhook_received',
                'entity_type' => 'payment',
                'entity_id' => $payment->id,
                'metadata' => [
                    'invoice_id' => $payment->invoice_id,
                    'amount_refunded' => $amountInDollars,
                    'is_full_refund' => $isFullRefund,
                    'stripe_refund_id' => $refundId,
                ],
            ]);

            Log::info('Refund processed from webhook: ' . $refundId . ' for payment: ' . $payment->id);
        });
    }
}

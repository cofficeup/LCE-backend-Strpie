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

class PaymentService
{
    protected StripeService $stripe;
    protected InvoiceService $invoices;

    public function __construct(StripeService $stripe, InvoiceService $invoices)
    {
        $this->stripe = $stripe;
        $this->invoices = $invoices;
    }

    /**
     * Create a payment intent for an invoice.
     * Returns client_secret for frontend.
     */
    public function createPaymentIntent(Invoice $invoice, User $user): array
    {
        // GUARD 1: Invoice already paid
        if ($invoice->isPaid()) {
            throw new \DomainException('Invoice already paid.');
        }

        // GUARD 2: Invoice already refunded
        if ($invoice->isRefunded()) {
            throw new \DomainException('Invoice has been refunded.');
        }

        // Validate invoice state
        if (!in_array($invoice->status, ['draft', 'pending_payment', 'payment_failed'])) {
            throw new \DomainException('Invoice is not payable. Current status: ' . $invoice->status);
        }

        // GUARD 3: Check for existing active payment (pending or succeeded)
        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCEEDED])
            ->first();

        if ($existingPayment) {
            if ($existingPayment->status === Payment::STATUS_SUCCEEDED) {
                throw new \DomainException('Payment already completed for this invoice.');
            }

            // Return existing pending payment intent
            if ($existingPayment->stripe_payment_intent_id) {
                $intent = $this->stripe->getPaymentIntent($existingPayment->stripe_payment_intent_id);
                return [
                    'payment_intent_id' => $intent->id,
                    'client_secret' => $intent->client_secret,
                    'amount' => $invoice->total,
                    'currency' => $invoice->currency ?? 'USD',
                    'payment_id' => $existingPayment->id,
                    'existing' => true,
                ];
            }
        }

        return DB::transaction(function () use ($invoice, $user) {
            // Mark invoice as pending payment
            if ($invoice->status === 'draft') {
                $this->invoices->markPendingPayment($invoice);
            }

            // Create Stripe payment intent
            $intentData = $this->stripe->createPaymentIntent($invoice, $user);

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $invoice->total,
                'currency' => $invoice->currency ?? 'USD',
                'status' => Payment::STATUS_PENDING,
                'stripe_payment_intent_id' => $intentData['payment_intent_id'],
            ]);

            return array_merge($intentData, [
                'payment_id' => $payment->id,
            ]);
        });
    }

    /**
     * Handle successful payment (from webhook).
     */
    public function markPaymentSucceeded(string $paymentIntentId): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning('Payment not found for intent: ' . $paymentIntentId);
            return;
        }

        // Idempotency: skip if already succeeded
        if ($payment->isSucceeded()) {
            Log::info('Payment already succeeded: ' . $paymentIntentId);
            return;
        }

        DB::transaction(function () use ($payment) {
            // Update payment
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
            ]);

            // Update invoice
            $invoice = $payment->invoice;
            if ($invoice && $invoice->status !== 'paid') {
                $this->invoices->markPaid($invoice, [
                    'payment_id' => $payment->id,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                ]);
            }

            // Audit log for payment success
            AuditLog::create([
                'user_id' => $payment->user_id,
                'action' => 'payment_succeeded',
                'entity_type' => 'payment',
                'entity_id' => $payment->id,
                'metadata' => [
                    'invoice_id' => $payment->invoice_id,
                    'amount' => $payment->amount,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                ],
            ]);

            Log::info('Payment succeeded: ' . $payment->stripe_payment_intent_id);
        });
    }

    /**
     * Handle failed payment (from webhook).
     */
    public function markPaymentFailed(string $paymentIntentId, ?string $reason = null): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning('Payment not found for intent: ' . $paymentIntentId);
            return;
        }

        // Idempotency: skip if already failed
        if ($payment->status === Payment::STATUS_FAILED) {
            Log::info('Payment already marked as failed: ' . $paymentIntentId);
            return;
        }

        DB::transaction(function () use ($payment, $reason) {
            // Update payment
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $reason,
            ]);

            // Update invoice
            $invoice = $payment->invoice;
            if ($invoice && $invoice->status === 'pending_payment') {
                $this->invoices->markPaymentFailed($invoice, $reason);
            }

            // Audit log for payment failure
            AuditLog::create([
                'user_id' => $payment->user_id,
                'action' => 'payment_failed',
                'entity_type' => 'payment',
                'entity_id' => $payment->id,
                'metadata' => [
                    'invoice_id' => $payment->invoice_id,
                    'amount' => $payment->amount,
                    'failure_reason' => $reason,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                ],
            ]);

            Log::info('Payment failed: ' . $payment->stripe_payment_intent_id . ' - ' . $reason);
        });
    }

    /**
     * Get payment by invoice ID.
     */
    public function getPaymentByInvoice(int $invoiceId): ?Payment
    {
        return Payment::where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}

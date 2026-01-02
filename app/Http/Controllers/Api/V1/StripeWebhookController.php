<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StripeWebhookEvent;
use App\Services\Stripe\StripeService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\RefundService;
use App\Services\Subscription\SubscriptionWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected StripeService $stripeService;
    protected PaymentService $paymentService;
    protected RefundService $refundService;
    protected SubscriptionWebhookHandler $subscriptionHandler;

    public function __construct(
        StripeService $stripeService,
        PaymentService $paymentService,
        RefundService $refundService,
        SubscriptionWebhookHandler $subscriptionHandler
    ) {
        $this->stripeService = $stripeService;
        $this->paymentService = $paymentService;
        $this->refundService = $refundService;
        $this->subscriptionHandler = $subscriptionHandler;
    }

    /**
     * Handle Stripe webhook events.
     * 
     * POST /api/v1/webhooks/stripe
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            Log::warning('Stripe webhook: Missing signature');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Verify webhook signature
        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (\RuntimeException $e) {
            Log::warning('Stripe webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Idempotency check: store event
        $webhookEvent = StripeWebhookEvent::findOrCreateEvent(
            $event->id,
            $event->type,
            $event->toArray()
        );

        if ($webhookEvent->isProcessed()) {
            Log::info('Stripe webhook: Event already processed - ' . $event->id);
            return response()->json(['status' => 'already_processed']);
        }

        // Handle event types
        try {
            $this->processEvent($event);
            $webhookEvent->markProcessed();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'type' => $event->type,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Process specific event types.
     */
    protected function processEvent(\Stripe\Event $event): void
    {
        $object = $event->data->object;

        switch ($event->type) {
            // ===============================
            // Payment Intent Events (PPO)
            // ===============================
            case 'payment_intent.succeeded':
                Log::info('Processing payment_intent.succeeded: ' . $object->id);
                $this->paymentService->markPaymentSucceeded($object->id);
                break;

            case 'payment_intent.payment_failed':
                Log::info('Processing payment_intent.payment_failed: ' . $object->id);
                $failureMessage = $object->last_payment_error->message ?? 'Payment failed';
                $this->paymentService->markPaymentFailed($object->id, $failureMessage);
                break;

            case 'charge.refunded':
                Log::info('Processing charge.refunded: ' . $object->id);
                $paymentIntentId = $object->payment_intent;
                $refundId = $object->refunds->data[0]->id ?? null;
                $amountRefunded = $object->amount_refunded ?? 0;

                if ($paymentIntentId && $refundId) {
                    // Handle PPO refunds
                    $this->refundService->handleRefundFromWebhook(
                        $paymentIntentId,
                        $refundId,
                        $amountRefunded
                    );

                    // Handle subscription refunds (partial refund support)
                    $this->subscriptionHandler->handleChargeRefunded($object);
                }
                break;

            // ===============================
            // Subscription Invoice Events
            // ===============================
            case 'invoice.paid':
                Log::info('Processing invoice.paid: ' . $object->id);
                $this->subscriptionHandler->handleInvoicePaid($object);
                break;

            case 'invoice.payment_failed':
                Log::info('Processing invoice.payment_failed: ' . $object->id);
                $this->subscriptionHandler->handleInvoicePaymentFailed($object);
                break;

            case 'invoice.finalized':
                Log::info('Processing invoice.finalized: ' . $object->id);
                $this->subscriptionHandler->handleInvoiceFinalized($object);
                break;

            // ===============================
            // Subscription Lifecycle Events
            // ===============================
            case 'customer.subscription.updated':
                Log::info('Processing customer.subscription.updated: ' . $object->id);
                $this->subscriptionHandler->handleSubscriptionUpdated($object);
                break;

            case 'customer.subscription.deleted':
                Log::info('Processing customer.subscription.deleted: ' . $object->id);
                $this->subscriptionHandler->handleSubscriptionDeleted($object);
                break;

            case 'customer.subscription.created':
                Log::info('Subscription created in Stripe: ' . $object->id);
                // Typically handled during local subscription creation
                break;

            default:
                Log::info('Unhandled Stripe event type: ' . $event->type);
        }
    }
}

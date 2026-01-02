<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Services\Stripe\StripeSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Carbon\Carbon;

class ReconcileSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:reconcile 
                            {--dry-run : Show what would be done without making changes}
                            {--force : Force reconciliation even for recently synced}';

    protected $description = 'Reconcile local subscriptions with Stripe to catch missed webhooks and data drift';

    protected StripeClient $stripe;
    protected bool $dryRun = false;
    protected int $synced = 0;
    protected int $errors = 0;

    public function __construct()
    {
        parent::__construct();
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');

        $this->info('🔄 Starting subscription reconciliation...');
        if ($this->dryRun) {
            $this->warn('   Running in DRY-RUN mode - no changes will be made');
        }

        // Get all active/pending subscriptions linked to Stripe
        $subscriptions = UserSubscription::whereNotNull('stripe_subscription_id')
            ->whereIn('status', [
                UserSubscription::STATUS_PENDING,
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_PAUSED,
                UserSubscription::STATUS_PAST_DUE,
            ])
            ->get();

        $this->info("   Found {$subscriptions->count()} subscriptions to check");

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        foreach ($subscriptions as $subscription) {
            try {
                $this->reconcileSubscription($subscription);
            } catch (\Exception $e) {
                $this->errors++;
                Log::error('Reconciliation failed for subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Check for expired subscriptions
        $this->checkExpiredSubscriptions();

        // Summary
        $this->info("✅ Reconciliation complete:");
        $this->info("   - Synced: {$this->synced}");
        $this->info("   - Errors: {$this->errors}");

        return $this->errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function reconcileSubscription(UserSubscription $subscription): void
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->retrieve(
                $subscription->stripe_subscription_id,
                ['expand' => ['latest_invoice']]
            );
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Subscription no longer exists in Stripe
            if (str_contains($e->getMessage(), 'No such subscription')) {
                $this->handleDeletedSubscription($subscription);
                return;
            }
            throw $e;
        }

        $changes = [];

        // Check status mismatch
        $localStatus = $subscription->status;
        $stripeStatus = $this->mapStripeStatus($stripeSubscription->status);

        if ($localStatus !== $stripeStatus) {
            $changes['status'] = $stripeStatus;
        }

        // Check period dates (only if Stripe has them)
        if ($stripeSubscription->current_period_start && $stripeSubscription->current_period_end) {
            $stripePeriodStart = Carbon::createFromTimestamp($stripeSubscription->current_period_start);
            $stripePeriodEnd = Carbon::createFromTimestamp($stripeSubscription->current_period_end);

            if (
                !$subscription->current_period_start ||
                $subscription->current_period_start->timestamp !== $stripePeriodStart->timestamp
            ) {
                $changes['current_period_start'] = $stripePeriodStart;
            }

            if (
                !$subscription->current_period_end ||
                $subscription->current_period_end->timestamp !== $stripePeriodEnd->timestamp
            ) {
                $changes['current_period_end'] = $stripePeriodEnd;
            }
        }

        // Check cancellation status
        if ($stripeSubscription->cancel_at_period_end !== $subscription->cancel_at_period_end) {
            $changes['cancel_at_period_end'] = $stripeSubscription->cancel_at_period_end;
        }

        // Apply changes
        if (!empty($changes)) {
            if (!$this->dryRun) {
                $subscription->update($changes);

                AuditLog::create([
                    'user_id' => $subscription->user_id,
                    'action' => 'subscription_reconciled',
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'metadata' => [
                        'changes' => $changes,
                        'stripe_status' => $stripeSubscription->status,
                    ],
                ]);
            }

            $this->synced++;
            $this->line("\n   Updated subscription #{$subscription->id}: " . json_encode(array_keys($changes)));
        }

        // Check for missing invoices
        $this->reconcileInvoices($subscription, $stripeSubscription);
    }

    protected function reconcileInvoices(UserSubscription $subscription, \Stripe\Subscription $stripeSubscription): void
    {
        // Get recent Stripe invoices for this subscription
        $stripeInvoices = $this->stripe->invoices->all([
            'subscription' => $subscription->stripe_subscription_id,
            'limit' => 10,
        ]);

        foreach ($stripeInvoices->data as $stripeInvoice) {
            // Check if we have this invoice locally
            $localInvoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();

            if (!$localInvoice && $stripeInvoice->status === 'paid') {
                // Missing paid invoice - create it
                if (!$this->dryRun) {
                    $this->createMissingInvoice($subscription, $stripeInvoice);
                }
                $this->line("\n   Created missing invoice: {$stripeInvoice->id}");
                $this->synced++;
            } elseif ($localInvoice && $this->invoiceNeedsUpdate($localInvoice, $stripeInvoice)) {
                // Invoice status mismatch
                if (!$this->dryRun) {
                    $localInvoice->update([
                        'status' => $this->mapInvoiceStatus($stripeInvoice->status),
                        'paid_at' => $stripeInvoice->status === 'paid' ? now() : null,
                    ]);
                }
                $this->line("\n   Updated invoice status: {$stripeInvoice->id}");
                $this->synced++;
            }
        }
    }

    protected function createMissingInvoice(UserSubscription $subscription, \Stripe\Invoice $stripeInvoice): void
    {
        Invoice::create([
            'stripe_invoice_id' => $stripeInvoice->id,
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'type' => 'subscription',
            'status' => $this->mapInvoiceStatus($stripeInvoice->status),
            'currency' => strtoupper($stripeInvoice->currency),
            'subtotal' => $stripeInvoice->subtotal / 100,
            'tax' => $stripeInvoice->tax ? $stripeInvoice->tax / 100 : 0,
            'total' => $stripeInvoice->total / 100,
            'issued_at' => Carbon::createFromTimestamp($stripeInvoice->created),
            'paid_at' => $stripeInvoice->status === 'paid' ? now() : null,
            'metadata' => ['source' => 'reconciliation'],
        ]);
    }

    protected function invoiceNeedsUpdate(Invoice $local, \Stripe\Invoice $stripe): bool
    {
        $expectedStatus = $this->mapInvoiceStatus($stripe->status);
        return $local->status !== $expectedStatus;
    }

    protected function mapInvoiceStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'paid' => 'paid',
            'open' => 'pending_payment',
            'void' => 'cancelled',
            'uncollectible' => 'payment_failed',
            default => 'pending',
        };
    }

    protected function handleDeletedSubscription(UserSubscription $subscription): void
    {
        if ($subscription->status !== UserSubscription::STATUS_CANCELLED) {
            if (!$this->dryRun) {
                $subscription->update([
                    'status' => UserSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

                if ($subscription->user->subscription_id === $subscription->id) {
                    $subscription->user->update(['subscription_id' => null]);
                }

                AuditLog::create([
                    'user_id' => $subscription->user_id,
                    'action' => 'subscription_cancelled_via_reconciliation',
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'metadata' => ['reason' => 'Subscription deleted in Stripe'],
                ]);
            }

            $this->synced++;
            $this->warn("\n   Subscription #{$subscription->id} deleted in Stripe - marked as cancelled");
        }
    }

    protected function checkExpiredSubscriptions(): void
    {
        $this->info('🔍 Checking for expired subscriptions...');

        // Find subscriptions past their period end that are still active
        $expired = UserSubscription::where('status', UserSubscription::STATUS_ACTIVE)
            ->where('cancel_at_period_end', true)
            ->where('current_period_end', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            if (!$this->dryRun) {
                $subscription->update([
                    'status' => UserSubscription::STATUS_CANCELLED,
                    'cancelled_at' => $subscription->current_period_end,
                ]);

                if ($subscription->user->subscription_id === $subscription->id) {
                    $subscription->user->update(['subscription_id' => null]);
                }
            }

            $this->synced++;
            $this->line("   Expired subscription #{$subscription->id} marked as cancelled");
        }

        $this->info("   Found {$expired->count()} expired subscriptions");
    }

    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => UserSubscription::STATUS_ACTIVE,
            'past_due' => UserSubscription::STATUS_PAST_DUE,
            'canceled' => UserSubscription::STATUS_CANCELLED,
            'paused' => UserSubscription::STATUS_PAUSED,
            'incomplete', 'incomplete_expired' => UserSubscription::STATUS_PENDING,
            default => UserSubscription::STATUS_PENDING,
        };
    }
}

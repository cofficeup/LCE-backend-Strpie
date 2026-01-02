<?php

namespace App\Services\Stripe;

use App\Models\SubscriptionPlan;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeProductService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Sync a plan to Stripe (create Product and Prices).
     */
    public function syncPlanToStripe(SubscriptionPlan $plan): SubscriptionPlan
    {
        // Create or update Product
        $productId = $this->syncProduct($plan);

        // Create Prices for each billing cycle
        $priceIds = $this->syncPrices($plan, $productId);

        // Update plan with Stripe IDs
        $plan->update(array_merge(
            ['stripe_product_id' => $productId],
            $priceIds
        ));

        Log::info('Plan synced to Stripe', [
            'plan_id' => $plan->id,
            'stripe_product_id' => $productId,
        ]);

        return $plan->fresh();
    }

    /**
     * Create or update Stripe Product.
     */
    protected function syncProduct(SubscriptionPlan $plan): string
    {
        try {
            if ($plan->stripe_product_id) {
                // Update existing product
                $this->stripe->products->update($plan->stripe_product_id, [
                    'name' => $plan->name,
                    'description' => $plan->description ?? "Subscription plan: {$plan->name}",
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'slug' => $plan->slug,
                    ],
                ]);

                return $plan->stripe_product_id;
            }

            // Create new product
            $product = $this->stripe->products->create([
                'name' => $plan->name,
                'description' => $plan->description ?? "Subscription plan: {$plan->name}",
                'metadata' => [
                    'plan_id' => $plan->id,
                    'slug' => $plan->slug,
                ],
            ]);

            return $product->id;
        } catch (ApiErrorException $e) {
            Log::error('Failed to sync product to Stripe', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create Stripe product: ' . $e->getMessage());
        }
    }

    /**
     * Create Prices for each billing cycle.
     */
    protected function syncPrices(SubscriptionPlan $plan, string $productId): array
    {
        $priceIds = [];

        $cycleConfigs = [
            SubscriptionPlan::CYCLE_DAILY => [
                'field' => 'stripe_price_id_daily',
                'price' => $plan->price_daily,
                'interval' => 'day',
                'interval_count' => 1,
            ],
            SubscriptionPlan::CYCLE_WEEKLY => [
                'field' => 'stripe_price_id_weekly',
                'price' => $plan->price_weekly,
                'interval' => 'week',
                'interval_count' => 1,
            ],
            SubscriptionPlan::CYCLE_MONTHLY => [
                'field' => 'stripe_price_id_monthly',
                'price' => $plan->price_monthly,
                'interval' => 'month',
                'interval_count' => 1,
            ],
            SubscriptionPlan::CYCLE_ANNUAL => [
                'field' => 'stripe_price_id_annual',
                'price' => $plan->price_annual,
                'interval' => 'year',
                'interval_count' => 1,
            ],
        ];

        foreach ($cycleConfigs as $cycle => $config) {
            // Skip if no price set for this cycle
            if (empty($config['price'])) {
                continue;
            }

            $existingPriceId = $plan->{$config['field']};

            // If price exists and matches, skip
            if ($existingPriceId) {
                try {
                    $existingPrice = $this->stripe->prices->retrieve($existingPriceId);
                    $existingAmount = $existingPrice->unit_amount;
                    $newAmount = (int) round($config['price'] * 100);

                    // If amount matches, reuse the price
                    if ($existingAmount === $newAmount) {
                        $priceIds[$config['field']] = $existingPriceId;
                        continue;
                    }

                    // Archive old price (Stripe doesn't allow updating price amounts)
                    $this->stripe->prices->update($existingPriceId, ['active' => false]);
                } catch (ApiErrorException $e) {
                    // Price doesn't exist, will create new one
                }
            }

            // Create new price
            try {
                $price = $this->stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => (int) round($config['price'] * 100),
                    'currency' => strtolower(config('stripe.currency', 'usd')),
                    'recurring' => [
                        'interval' => $config['interval'],
                        'interval_count' => $config['interval_count'],
                    ],
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'billing_cycle' => $cycle,
                    ],
                ]);

                $priceIds[$config['field']] = $price->id;

                Log::info('Created Stripe price', [
                    'plan_id' => $plan->id,
                    'cycle' => $cycle,
                    'price_id' => $price->id,
                ]);
            } catch (ApiErrorException $e) {
                Log::error('Failed to create Stripe price', [
                    'plan_id' => $plan->id,
                    'cycle' => $cycle,
                    'error' => $e->getMessage(),
                ]);
                throw new \RuntimeException("Failed to create Stripe price for {$cycle}: " . $e->getMessage());
            }
        }

        return $priceIds;
    }

    /**
     * Sync all active plans to Stripe.
     */
    public function syncAllPlansToStripe(): array
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        $results = [];

        foreach ($plans as $plan) {
            try {
                $this->syncPlanToStripe($plan);
                $results[$plan->id] = ['status' => 'success', 'plan' => $plan->name];
            } catch (\Exception $e) {
                $results[$plan->id] = ['status' => 'error', 'plan' => $plan->name, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Archive a plan in Stripe (deactivate product).
     */
    public function archivePlan(SubscriptionPlan $plan): bool
    {
        if (!$plan->stripe_product_id) {
            return true;
        }

        try {
            $this->stripe->products->update($plan->stripe_product_id, [
                'active' => false,
            ]);

            Log::info('Archived Stripe product', ['plan_id' => $plan->id]);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to archive Stripe product', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

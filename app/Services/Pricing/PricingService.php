<?php

namespace App\Services\Pricing;

use App\Models\PriceList;
use App\Models\PricingItem;
use App\Services\Pickup\ZoneService;

class PricingService
{
    protected ZoneService $zones;

    public function __construct(ZoneService $zones)
    {
        $this->zones = $zones;
    }

    /**
     * Get the active price list for a user based on their ZIP code.
     */
    public function getPriceListForZip(string $zipCode): ?PriceList
    {
        // 1. Try to find a specific list for this ZIP
        $lists = PriceList::active()->orderBy('display_order')->get();

        foreach ($lists as $list) {
            if ($list->zip_codes) {
                $zips = array_map('trim', explode(',', $list->zip_codes));
                if (in_array($zipCode, $zips)) {
                    return $list;
                }
            }
        }

        // Fallback: Default Residential List (usually the one with lowest order that is residential)
        return PriceList::active()
            ->where('type', 'residential')
            ->orderBy('display_order')
            ->first();
    }

    /**
     * Calculate estimated total for PPO order.
     */
    public function calculatePPOEstimate(array $items, string $zipCode): array
    {
        $priceList = $this->getPriceListForZip($zipCode);

        if (!$priceList) {
            throw new \Exception("No pricing available for this area.");
        }

        $total = 0;
        $lineItems = [];

        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = $item['quantity'];

            $pricingItem = PricingItem::where('sku', $sku)->active()->first();

            if (!$pricingItem) continue;

            $price = $pricingItem->getPriceForList($priceList->id);

            if ($price === null) continue; // Item not available in this list

            $lineTotal = $price * $qty;

            $total += $lineTotal;
            $lineItems[] = [
                'sku' => $sku,
                'name' => $pricingItem->item_name,
                'unit_price' => $price,
                'quantity' => $qty,
                'total' => $lineTotal
            ];
        }

        return [
            'price_list_id' => $priceList->id,
            'price_list_name' => $priceList->name,
            'subtotal' => $total,
            'items' => $lineItems
        ];
    }

    /**
     * Calculate PPO totals (Legacy/Math Helper).
     */
    public function calculatePPO(float $weight, float $rate, float $min, float $pickupFee, float $serviceFee): array
    {
        $weightCost = $weight * $rate;
        $subtotal = max($weightCost, $min);
        $total = $subtotal + $pickupFee + $serviceFee;

        return [
            'weight_cost' => $weightCost,
            'min_adjustment' => $total - ($weightCost + $pickupFee + $serviceFee), // simplified
            'subtotal' => $subtotal,
            'fees' => $pickupFee + $serviceFee,
            'total' => $total
        ];
    }

    /**
     * Calculate Subscription Overage (Math Helper).
     */
    public function calculateSubscriptionOverage(float $actualWeight, int $bagsUsed, float $maxPerBag, float $overageRate): array
    {
        $allowedWeight = $bagsUsed * $maxPerBag;
        $overweight = max(0, $actualWeight - $allowedWeight);
        $charge = $overweight * $overageRate;

        return [
            'allowed_weight' => $allowedWeight,
            'overweight_lbs' => $overweight,
            'overage_rate' => $overageRate,
            'overage_charge' => $charge,
            'total' => $charge
        ];
    }

    /**
     * Apply credits to a total amount.
     */
    public function applyCredits(float $total, float $creditsAvailable): array
    {
        $creditsUsed = min($total, $creditsAvailable);
        $finalTotal = $total - $creditsUsed;

        return [
            'original_total' => $total,
            'credits_available' => $creditsAvailable,
            'credits_used' => $creditsUsed,
            'final_total' => $finalTotal
        ];
    }
}

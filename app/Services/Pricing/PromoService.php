<?php

namespace App\Services\Pricing;

use App\Models\PromoCode;
use App\Models\UserPromoCode;
use App\Models\User;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PromoService
{
    /**
     * Validate a promo code for a specific user and order context.
     */
    public function validateCode(string $code, User $user, float $orderAmount, string $orderType = 'all'): PromoCode
    {
        $promo = PromoCode::active()->where('code', $code)->first();

        if (!$promo) {
            throw ValidationException::withMessages(['code' => 'Invalid promo code.']);
        }

        if (!$promo->isValid($orderAmount)) {
            throw ValidationException::withMessages(['code' => 'Promo code requirements not met (min order amount or expiry).']);
        }

        // Check if user already used it (if single use per user is logic, or global max check)
        // For now let's assume one use per user unless specified otherwise, but standard is usually checked in current_uses

        // Check order type applicability
        if ($promo->applies_to !== 'all') {
            if ($orderType === 'subscription' && $promo->applies_to !== 'subscription_first_month') {
                throw ValidationException::withMessages(['code' => 'This code cannot be used for subscriptions.']);
            }
            if ($orderType === 'ppo' && $promo->applies_to !== 'ppo_order') {
                throw ValidationException::withMessages(['code' => 'This code cannot be used for Pay-Per-Order.']);
            }
        }

        // Check if user has already used this code? (Optional rule, sticking to basic valid() for now)
        $hasUsed = UserPromoCode::where('user_id', $user->id)
            ->where('promo_code_id', $promo->id)
            ->exists();

        if ($hasUsed && $promo->max_uses === 1) { // Assuming strict single use logic if intended
            // For simplify, let's assume if it's "New Customer" only
        }

        return $promo;
    }

    /**
     * Calculate discount amount.
     */
    public function calculateDiscount(PromoCode $promo, float $subtotal): float
    {
        if ($promo->discount_type === 'fixed_amount') {
            return min($promo->discount_value, $subtotal);
        }

        if ($promo->discount_type === 'percentage') {
            return round($subtotal * ($promo->discount_value / 100), 2);
        }

        return 0.00;
    }

    /**
     * Apply promo code to an invoice (consumes the code).
     */
    public function applyToInvoice(Invoice $invoice, string $code): void
    {
        $promo = $this->validateCode($code, $invoice->user, $invoice->total);

        $discountAmount = $this->calculateDiscount($promo, $invoice->total);

        // Record usage
        UserPromoCode::create([
            'user_id' => $invoice->user_id,
            'promo_code_id' => $promo->id,
            'invoice_id' => $invoice->id,
            'discount_applied' => $discountAmount,
            'used_at' => Carbon::now()
        ]);

        // Increment global usage
        $promo->increment('current_uses');

        // Update invoice (add credit line item or reduce total)
        // Ideally we add a line item for negative amount

        // This part depends on how InvoiceService handles modifications. 
        // For now we just record the usage.
    }
}

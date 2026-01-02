<?php

namespace App\Services\Pickup;

use App\Models\User;
use App\Models\Pickup;
use App\Models\UserSubscription;
use App\Services\Billing\BillingService;
use App\Services\Subscription\SubscriptionService;
use App\Services\Invoice\InvoiceService;
use App\Exceptions\PickupSchedulingException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PickupService
{
    protected BillingService $billing;
    protected SubscriptionService $subscriptions;
    protected InvoiceService $invoices;
    protected ZoneService $zones;
    protected HolidayService $holidays;

    public function __construct(
        BillingService $billing,
        SubscriptionService $subscriptions,
        InvoiceService $invoices,
        ZoneService $zones,
        HolidayService $holidays
    ) {
        $this->billing = $billing;
        $this->subscriptions = $subscriptions;
        $this->invoices = $invoices;
        $this->zones = $zones;
        $this->holidays = $holidays;
    }

    /**
     * Create a PPO (Pay-Per-Order) pickup preview.
     */
    public function createPPOPickup(User $user, array $data): array
    {
        // 🚨 ENFORCE SCHEDULING & CUTOFF RULES
        $this->enforceSchedulingRules($data['pickup_date'] ?? null, $user);

        // 1️⃣ Validate input
        if (empty($data['estimated_weight']) || $data['estimated_weight'] <= 0) {
            throw new \InvalidArgumentException('Estimated weight must be greater than zero.');
        }

        // 2️⃣ Billing preview (static pricing for now)
        $billingPreview = $this->billing->billPPO(
            $user,
            $data['estimated_weight'],
            1.99,   // price per lb
            30.00,  // minimum charge
            5.00,   // pickup fee
            3.00    // service fee
        );

        // 3️⃣ Invoice preview (draft only, NOT persisted)
        $invoicePreview = $this->invoices->createDraft(
            userId: $user->id,
            invoiceType: 'ppo',
            billingPreview: $billingPreview,
            pickupId: null
        );

        // 4️⃣ Prepare pickup payload (NOT saved yet)
        $pickupPayload = [
            'order_type' => 'ppo',
            'status' => $billingPreview['requires_payment']
                ? 'pending_payment'
                : 'scheduled',
            'pickup_date' => $data['pickup_date'] ?? null,
            'estimated_weight' => $data['estimated_weight'],
            'bags_used' => null,
            'subscription_id' => null,
        ];

        return [
            'pickup_payload' => $pickupPayload,
            'billing_preview' => $billingPreview,
            'invoice_preview' => $invoicePreview,
        ];
    }

    /**
     * Create a subscription pickup preview.
     */
    public function createSubscriptionPickup(
        User $user,
        UserSubscription $subscription,
        array $data
    ): array {
        // 🚨 ENFORCE SCHEDULING & CUTOFF RULES
        $this->enforceSchedulingRules($data['pickup_date'] ?? null, $user);

        // 1️⃣ Validate subscription
        if ($subscription->status !== 'active') {
            throw new \DomainException('Subscription must be active to create a pickup.');
        }

        if (empty($data['bags']) || $data['bags'] <= 0) {
            throw new \InvalidArgumentException('At least one bag is required.');
        }

        // 2️⃣ Check available bags
        $availableBags = $this->subscriptions
            ->calculateAvailableBags($subscription);

        if ($data['bags'] > $availableBags) {
            throw new \DomainException('Not enough subscription bags available.');
        }

        // 3️⃣ Billing preview (overage only)
        $billingPreview = $this->billing->billSubscriptionOverage(
            $user,
            $subscription,
            $data['estimated_weight'] ?? 0,
            $data['bags'],
            20,     // max weight per bag (lbs)
            2.50    // overage price per lb
        );

        // 4️⃣ Invoice preview (draft only)
        $invoicePreview = $this->invoices->createDraft(
            userId: $user->id,
            invoiceType: 'subscription_overage',
            billingPreview: $billingPreview,
            pickupId: null,
            subscriptionId: $subscription->id
        );

        // 5️⃣ Prepare pickup payload
        $pickupPayload = [
            'order_type' => 'subscription',
            'status' => 'scheduled',
            'pickup_date' => $data['pickup_date'] ?? null,
            'estimated_weight' => $data['estimated_weight'] ?? null,
            'bags_used' => $data['bags'],
            'subscription_id' => $subscription->id,
        ];

        return [
            'pickup_payload' => $pickupPayload,
            'billing_preview' => $billingPreview,
            'invoice_preview' => $invoicePreview,
        ];
    }

    /**
     * Confirm and persist a pickup atomically with invoice.
     */
    public function confirmPickup(User $user, array $data): array
    {
        // 1️⃣ Enforce scheduling rules
        $this->enforceSchedulingRules($data['pickup_date'] ?? null, $user);

        return DB::transaction(function () use ($user, $data) {

            // 2️⃣ Create pickup
            $pickup = Pickup::create([
                'user_id' => $user->id,
                'order_type' => strtolower($data['order_type']),
                'status' => 'scheduled',
                'pickup_date' => $data['pickup_date'],
                'estimated_weight' => $data['estimated_weight'] ?? null,
                'bags_used' => $data['bags'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,
            ]);

            // 3️⃣ Create invoice (persisted)
            $invoice = $this->invoices->createAndPersistDraft(
                userId: $user->id,
                invoiceType: $data['invoice_type'],
                billingPreview: $data['billing_preview'],
                pickupId: $pickup->id,
                subscriptionId: $data['subscription_id'] ?? null
            );

            // 4️⃣ Link invoice to pickup
            $pickup->update([
                'invoice_id' => $invoice->id,
            ]);

            return [
                'pickup' => $pickup->fresh(),
                'invoice' => $invoice->load('lines'),
            ];
        });
    }

    /**
     * Enforce pickup scheduling & cutoff rules.
     */
    protected function enforceSchedulingRules(?string $pickupDate, User $user): void
    {
        $timezone = config('pickups.timezone');
        $cutoffHour = (int) config('pickups.cutoff_hour');
        $allowSameDay = (bool) config('pickups.allow_same_day');

        $now = Carbon::now($timezone);
        $userZip = $user->zip; // Assuming access to zip directly

        // 1. Zone Validation (If user has a zip)
        if ($userZip) {
            if (!$this->zones->isServiceable($userZip)) {
                throw new PickupSchedulingException(
                    "Service is not available in your area ($userZip)."
                );
            }
        }

        if (!$pickupDate) {
            return;
        }

        $requested = Carbon::parse($pickupDate, $timezone)->startOfDay();
        $today = $now->copy()->startOfDay();

        // 2. Reject past dates
        if ($requested->lt($today)) {
            throw new PickupSchedulingException(
                'Cannot schedule pickup for a past date. Please select today or a future date.'
            );
        }

        // 3. Same-day not allowed
        if (!$allowSameDay && $requested->isSameDay($now)) {
            throw new PickupSchedulingException(
                'Same-day pickups are not available. Please select the next available date.'
            );
        }

        // 4. Cutoff enforcement
        if ($requested->isSameDay($now) && $now->hour >= $cutoffHour) {
            throw new PickupSchedulingException(
                'Pickup cutoff time has passed. Please select the next available date.'
            );
        }

        // 5. Holiday Validation
        if ($this->holidays->isHoliday($pickupDate)) {
            $holiday = $this->holidays->getHoliday($pickupDate);
            throw new PickupSchedulingException(
                "Pickup not available on holidays: {$holiday->holiday_name}"
            );
        }

        // 6. Service Day Validation (If user has zip)
        if ($userZip) {
            $check = $this->zones->isAvailableOnDate($userZip, $pickupDate);
            if (!$check['available']) {
                throw new PickupSchedulingException(
                    $check['reason'] ?? 'Service not available on this date'
                );
            }
        }
    }
}

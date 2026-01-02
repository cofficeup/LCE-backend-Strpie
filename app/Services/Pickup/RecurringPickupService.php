<?php

namespace App\Services\Pickup;

use App\Models\RecurringSchedule;
use App\Models\Pickup;
use App\Services\Pickup\PickupService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringPickupService
{
    protected PickupService $pickupService;

    public function __construct(PickupService $pickupService)
    {
        $this->pickupService = $pickupService;
    }

    /**
     * Generate pickups for a specific date based on recurring schedules.
     */
    public function generatePickupsForDate(Carbon $date): int
    {
        $dayOfWeek = strtolower($date->format('l'));
        $scheduleField = 'schedule_' . $dayOfWeek;
        $dateStr = $date->format('Y-m-d');

        // Find active schedules for this day
        $schedules = RecurringSchedule::where('active', true)
            ->where($scheduleField, true)
            ->where('start_date', '<=', $dateStr)
            ->where(function ($query) use ($dateStr) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $dateStr);
            })
            ->with(['user', 'user.activeSubscription'])
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            try {
                // Check if pickup already exists for this date
                $exists = Pickup::where('user_id', $schedule->user_id)
                    ->where('pickup_date', $dateStr)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if ($exists) {
                    continue; // Skip if already scheduled
                }

                // Prepare data
                $pickupData = [
                    'order_type' => $schedule->order_type === 'subscription' ? 'subscription' : 'ppo',
                    'pickup_date' => $dateStr,
                    'estimated_weight' => $schedule->default_weight ?? 15,
                    'bags' => $schedule->default_bags ?? 1,
                    'subscription_id' => null,
                    'invoice_type' => $schedule->order_type === 'subscription' ? 'subscription_overage' : 'ppo',
                    'billing_preview' => [] // Will be generated
                ];

                // Logic differs slightly from realtime preview -> confirm
                // We need to simulate the flow

                if ($schedule->order_type === 'subscription') {
                    if (!$schedule->user->activeSubscription) {
                        Log::warning("Skipping recurring pickup for User {$schedule->user_id}: No active subscription.");
                        continue;
                    }

                    // Generate preview
                    $preview = $this->pickupService->createSubscriptionPickup(
                        $schedule->user,
                        $schedule->user->activeSubscription,
                        $pickupData
                    );

                    $pickupData['subscription_id'] = $schedule->user->activeSubscription->id;
                    $pickupData['billing_preview'] = $preview['billing_preview'];
                } else {
                    // PPO
                    $preview = $this->pickupService->createPPOPickup(
                        $schedule->user,
                        $pickupData
                    );

                    $pickupData['billing_preview'] = $preview['billing_preview'];
                }

                // Confirm (create)
                $this->pickupService->confirmPickup($schedule->user, $pickupData);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to generate recurring pickup for User {$schedule->user_id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}

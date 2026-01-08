<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TestModels extends Command
{
    protected $signature = 'test:models';
    protected $description = 'Test all database models';

    public function handle()
    {
        $models = [
            'CommunicationSettings' => 'lce_communication_settings',
            'Configuration' => 'lce_configurations',
            'Credit' => 'lce_user_credits',
            'CustomerSupport' => 'lce_user_cs',
            'CustomerSupportLog' => 'lce_user_cs_log',
            'GroupAdmin' => 'lce_user_group_admin',
            'GroupLog' => 'lce_user_group_log',
            'GroupMember' => 'lce_user_group_members',
            'GroupMemberHistory' => 'lce_user_group_members_history',
            'HolidayLog' => 'lce_holidays_logs',
            'Invoice' => 'lce_user_invoice',
            'InvoiceLine' => 'lce_user_invoice_line',
            'NoLaundrySmsLog' => 'lce_nolaundry_sms_log',
            'Payment' => 'lce_payment',
            'Pickup' => 'lce_user_pickup',
            'PickupHoliday' => 'lce_pickup_nonworking_days',
            'PickupZone' => 'lce_pickup_zones',
            'Price' => 'lce_prices',
            'PriceList' => 'lce_prices_lists',
            'ProcessingSite' => 'lce_processing_sites',
            'PromoCode' => 'lce_promo_codes',
            'RecurringSchedule' => 'lce_user_rs',
            'SubscriptionPlan' => 'lce_subscription_plans',
            'SubscriptionUsage' => 'lce_user_subscription_usage',
            'TmpGroupMember' => 'lce_tmp_group_members',
            'TmpInvitedGroupMembersLog' => 'lce_tmp_invited_group_members_log',
            'TmpNoLaundryTransaction' => 'lce_tmp_nolaundry_transactions',
            'TmpRegisterEmail' => 'lce_tmp_register_emails',
            'Transaction' => 'lce_user_transactions',
            'User' => 'lce_user_info',
            'UserPromoCode' => 'lce_user_promocode',
            'UserSubscription' => 'lce_user_subscriptions',
            'VacationLog' => 'lce_users_vacation_logs',
            'WaitingList' => 'lce_waiting_list',
        ];

        $this->info("Testing 34 Database Models...\n");

        $passed = 0;
        $failed = 0;

        foreach ($models as $model => $table) {
            try {
                // Check if table exists
                if (!Schema::hasTable($table)) {
                    $this->error("✗ {$model}: Table '{$table}' does not exist");
                    $failed++;
                    continue;
                }

                // Try to count records
                $class = "App\\Models\\{$model}";
                $count = $class::count();
                $this->line("✓ {$model}: {$count} records");
                $passed++;
            } catch (\Exception $e) {
                $this->error("✗ {$model}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("Results: {$passed} passed, {$failed} failed");
        $this->info("========================================");

        return $failed === 0 ? 0 : 1;
    }
}

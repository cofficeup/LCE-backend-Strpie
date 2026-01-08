<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Import the legacy LCE database schema.
     */
    public function up(): void
    {
        // Read the legacy SQL file
        $sqlPath = database_path('sql/lce_site_v2.sql');

        if (!file_exists($sqlPath)) {
            throw new \Exception("Legacy SQL file not found at: {$sqlPath}");
        }

        $sql = file_get_contents($sqlPath);

        // Execute the SQL statements
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all legacy tables
        $tables = [
            'lce_communication_settings',
            'lce_configurations',
            'lce_holidays_logs',
            'lce_nolaundry_sms_log',
            'lce_payment',
            'lce_pickup_nonworking_days',
            'lce_pickup_zones',
            'lce_prices',
            'lce_prices_copy1',
            'lce_prices_lists',
            'lce_processing_sites',
            'lce_processing_sites_8aug',
            'lce_promo_codes',
            'lce_subscription_plans',
            'lce_tmp_group_members',
            'lce_tmp_invited_group_members_log',
            'lce_tmp_nolaundry_transactions',
            'lce_tmp_register_emails',
            'lce_user_credits',
            'lce_user_cs',
            'lce_user_cs_log',
            'lce_user_group_admin',
            'lce_user_group_log',
            'lce_user_group_members',
            'lce_user_group_members_history',
            'lce_user_info',
            'lce_user_invoice',
            'lce_user_invoice_line',
            'lce_user_pickup',
            'lce_user_promocode',
            'lce_user_promocodes',
            'lce_user_rs',
            'lce_user_subscription_usage',
            'lce_user_subscriptions',
            'lce_user_transactions',
            'lce_user_transactions_bk',
            'lce_users_vacation_logs',
            'lce_waiting_list',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};

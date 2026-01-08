<?php

// Database Model Test Script
// Run with: php artisan tinker < test_models.php

$models = [
    'CommunicationSettings',
    'Configuration',
    'Credit',
    'CustomerSupport',
    'CustomerSupportLog',
    'GroupAdmin',
    'GroupLog',
    'GroupMember',
    'GroupMemberHistory',
    'HolidayLog',
    'Invoice',
    'InvoiceLine',
    'NoLaundrySmsLog',
    'Payment',
    'Pickup',
    'PickupHoliday',
    'PickupZone',
    'Price',
    'PriceList',
    'ProcessingSite',
    'PromoCode',
    'RecurringSchedule',
    'SubscriptionPlan',
    'SubscriptionUsage',
    'TmpGroupMember',
    'TmpInvitedGroupMembersLog',
    'TmpNoLaundryTransaction',
    'TmpRegisterEmail',
    'Transaction',
    'User',
    'UserPromoCode',
    'UserSubscription',
    'VacationLog',
    'WaitingList',
];

echo "Testing 34 Database Models...\n\n";

$passed = 0;
$failed = 0;

foreach ($models as $model) {
    try {
        $class = "App\\Models\\{$model}";
        $count = $class::count();
        echo "✓ {$model}: {$count} records\n";
        $passed++;
    } catch (Exception $e) {
        echo "✗ {$model}: FAILED - " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n========================================\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo "========================================\n";

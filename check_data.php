<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'pickup_zones' => \App\Models\PickupZone::count(),
    'pricing_items' => \App\Models\PricingItem::count(),
    'promo_codes' => \App\Models\PromoCode::count(),
    'processing_sites' => \App\Models\ProcessingSite::count(),
    'recurring_schedules' => \App\Models\RecurringSchedule::count(),
    'users' => \App\Models\User::count(),
];

echo "Table Row Counts:\n";
foreach ($tables as $table => $count) {
    echo " - $table: $count\n";
}

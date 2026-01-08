<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LCE Authentication Diagnostic ===\n\n";

// 1. Check Database Connection
echo "1. Database Connection:\n";
try {
    $userCount = \App\Models\User::count();
    echo "   ✅ Connected. Users in DB: {$userCount}\n\n";
} catch (Exception $e) {
    echo "   ❌ Database Error: {$e->getMessage()}\n\n";
    exit(1);
}

// 2. Check if Roles exist
echo "2. Roles Table:\n";
$roles = \App\Models\Role::all();
if ($roles->count() > 0) {
    echo "   ✅ Roles exist: " . $roles->pluck('name')->join(', ') . "\n\n";
} else {
    echo "   ❌ NO ROLES FOUND! Run: php artisan db:seed --class=RoleSeeder\n\n";
}

// 3. Check if Customer Role exists
echo "3. Customer Role Check:\n";
$customerRole = \App\Models\Role::where('name', 'customer')->first();
if ($customerRole) {
    echo "   ✅ 'customer' role exists (ID: {$customerRole->id})\n\n";
} else {
    echo "   ❌ 'customer' role MISSING! Registration will fail silently on role assignment.\n\n";
}

// 4. Check User table structure  
echo "4. User Table Structure (lce_user_info):\n";
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('lce_user_info');
    echo "   Columns: " . implode(', ', $columns) . "\n\n";
} catch (Exception $e) {
    echo "   ❌ Error checking table: {$e->getMessage()}\n\n";
}

// 5. Try to find existing user
echo "5. Test User Lookup:\n";
$testUser = \App\Models\User::where('email', 'admin@example.com')->first();
if ($testUser) {
    echo "   ✅ Found admin@example.com (ID: {$testUser->id})\n";
    echo "   Password hash present: " . (strlen($testUser->password) > 0 ? 'Yes' : 'No') . "\n\n";
} else {
    echo "   ⚠️ admin@example.com not found. Run: php artisan db:seed\n\n";
}

// 6. Test Password Verification
echo "6. Password Verification Test:\n";
if ($testUser) {
    $testPassword = 'password';
    $match = \Illuminate\Support\Facades\Hash::check($testPassword, $testUser->password);
    echo "   Testing 'password' against hash: " . ($match ? '✅ MATCH' : '❌ NO MATCH') . "\n\n";
}

// 7. Check personal_access_tokens table
echo "7. Sanctum Tokens Table:\n";
try {
    $hasTable = \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens');
    if ($hasTable) {
        echo "   ✅ personal_access_tokens table exists\n\n";
    } else {
        echo "   ❌ personal_access_tokens table MISSING! Run: php artisan migrate\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
}

echo "=== Diagnostic Complete ===\n";

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\PickupController;
use App\Http\Controllers\Api\V1\CreditController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\Admin\AdminInvoiceController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\ZoneCheckController;
use App\Http\Controllers\Api\V1\Admin\ZoneController;
use App\Http\Controllers\Api\V1\Admin\HolidayController;
use App\Http\Controllers\Api\V1\Admin\PricingController;
use App\Http\Controllers\Api\V1\Admin\PromoCodeController;
use App\Http\Controllers\Api\V1\Admin\ProcessingSiteController;
use App\Http\Controllers\Api\V1\RecurringScheduleController;


/*
|--------------------------------------------------------------------------
| Health Check (Public)
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);
Route::get('v1/zones/check', [ZoneCheckController::class, 'check']);

/*
|--------------------------------------------------------------------------
| Public Auth Routes (Strict Rate Limit)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')
    ->middleware('throttle:10,1') // 10 requests per minute for auth
    ->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });


/*
|--------------------------------------------------------------------------
| Protected Routes (Auth + API Rate Limit)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->group(function () {

        // Auth (protected)
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Pickups
        Route::post('/pickups/preview', [PickupController::class, 'preview']);
        Route::post('/pickups/confirm', [PickupController::class, 'confirm']);
        Route::put('/pickups/{id}', [PickupController::class, 'update']);

        // Subscriptions
        Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
        Route::get('/subscriptions/check-pickup', [SubscriptionController::class, 'checkPickup']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/subscriptions/{id}/reactivate', [SubscriptionController::class, 'reactivate']);
        Route::post('/subscriptions/{id}/pause', [SubscriptionController::class, 'pause']);
        Route::post('/subscriptions/{id}/resume', [SubscriptionController::class, 'resume']);
        Route::post('/subscriptions/{id}/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::post('/subscriptions/{id}/downgrade', [SubscriptionController::class, 'downgrade']);

        // Billing
        Route::post('/billing/ppo/preview', [BillingController::class, 'ppoPreview']);

        // Credits
        Route::get('/credits', [CreditController::class, 'index']);

        // Invoices (customer)
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);

        // Payments
        Route::post('/payments/intent', [PaymentController::class, 'createIntent']);
        Route::get('/payments/status/{invoice}', [PaymentController::class, 'status']);

        // Recurring Schedules
        Route::apiResource('recurring-schedules', RecurringScheduleController::class);
    });


/*
|--------------------------------------------------------------------------
| Stripe Webhook (No Auth - Uses Signature Verification)
|--------------------------------------------------------------------------
*/

Route::post('/v1/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['throttle:api']);


/*
|--------------------------------------------------------------------------
| Admin Routes (Auth + Admin Role + Stricter Rate Limit)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin', 'throttle:30,1']) // 30 requests per minute
    ->group(function () {

        // Admin Invoices
        Route::get('/invoices', [AdminInvoiceController::class, 'index']);
        Route::get('/invoices/export', [AdminInvoiceController::class, 'export']);
        Route::post('/invoices/{invoice}/refund', [AdminInvoiceController::class, 'refund']);

        // Admin Dashboard
        Route::get('/dashboard/summary', [AdminDashboardController::class, 'summary']);
        Route::get('/dashboard/revenue', [AdminDashboardController::class, 'revenue']);

        // Admin Subscription Plans
        Route::get('/subscription-plans', [AdminSubscriptionController::class, 'listPlans']);
        Route::post('/subscription-plans', [AdminSubscriptionController::class, 'createPlan']);
        Route::put('/subscription-plans/{id}', [AdminSubscriptionController::class, 'updatePlan']);
        Route::post('/subscription-plans/{id}/sync', [AdminSubscriptionController::class, 'syncPlanToStripe']);
        Route::post('/subscription-plans/sync-all', [AdminSubscriptionController::class, 'syncAllPlansToStripe']);

        // Admin Subscriptions
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'listSubscriptions']);
        Route::get('/subscriptions/{id}/history', [AdminSubscriptionController::class, 'billingHistory']);
        Route::post('/subscriptions/{id}/upgrade', [AdminSubscriptionController::class, 'forceUpgrade']);
        Route::post('/subscriptions/{id}/downgrade', [AdminSubscriptionController::class, 'forceDowngrade']);
        Route::post('/subscriptions/{id}/proration', [AdminSubscriptionController::class, 'applyManualProration']);
        Route::post('/subscriptions/{id}/cancel', [AdminSubscriptionController::class, 'cancelImmediately']);

        // Admin Pickup Zones AND Holidays
        Route::apiResource('zones', ZoneController::class);
        Route::apiResource('holidays', HolidayController::class);

        // Pricing & Promos
        Route::get('pricing/items', [PricingController::class, 'items']);
        Route::post('pricing/items', [PricingController::class, 'storeItem']);
        Route::get('pricing/lists', [PricingController::class, 'lists']);
        Route::post('pricing/lists', [PricingController::class, 'storeList']);
        Route::post('pricing/lists/{id}/prices', [PricingController::class, 'updateListPrices']);

        Route::apiResource('promos', PromoCodeController::class);

        // Processing Sites
        Route::apiResource('sites', ProcessingSiteController::class);
    });

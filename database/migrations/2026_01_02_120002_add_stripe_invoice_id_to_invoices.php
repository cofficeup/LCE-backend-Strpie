<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add stripe_invoice_id if not exists
        if (!Schema::hasColumn('invoices', 'stripe_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('stripe_invoice_id')->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'stripe_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('stripe_invoice_id');
            });
        }
    }
};

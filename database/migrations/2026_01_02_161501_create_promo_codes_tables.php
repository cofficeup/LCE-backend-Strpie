<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Promo Codes Definition
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();

            // Discount Logic
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_shipping']);
            $table->decimal('discount_value', 10, 2)->default(0);

            // Constraints
            $table->integer('max_uses')->nullable();
            $table->integer('current_uses')->default(0);
            $table->decimal('min_order_amount', 10, 2)->nullable();

            // Availability
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('applies_to', ['all', 'subscription_first_month', 'ppo_order'])->default('all');

            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 2. User Promo Usage
        Schema::create('user_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');

            // Link to usage
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');

            $table->decimal('discount_applied', 10, 2);
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_promo_codes');
        Schema::dropIfExists('promo_codes');
    }
};

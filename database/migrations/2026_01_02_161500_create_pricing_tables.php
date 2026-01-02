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
        // 1. Price Lists (e.g. "Standard SF", "Commercial")
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->enum('type', ['residential', 'commercial'])->default('residential');
            $table->string('zip_codes', 1000)->nullable()->comment('Comma separated list of zips');
            $table->integer('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 2. Pricing Items (e.g. "Wash & Fold", "Men's Shirt")
        Schema::create('pricing_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 20)->unique();
            $table->enum('service_type', ['wash_fold', 'dry_clean', 'household', 'other'])->default('other');
            $table->string('item_name', 100);
            $table->text('description')->nullable();
            $table->string('unit', 20)->default('item'); // lb, item, sqft
            $table->integer('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['service_type', 'active']);
        });

        // 3. Price List Items (Pivot with price)
        Schema::create('pricing_item_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('price_list_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('min_price', 10, 2)->nullable(); // e.g. min $30 for W&F
            $table->timestamps();

            $table->unique(['pricing_item_id', 'price_list_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_item_prices');
        Schema::dropIfExists('pricing_items');
        Schema::dropIfExists('price_lists');
    }
};

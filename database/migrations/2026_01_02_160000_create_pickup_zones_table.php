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
        Schema::create('pickup_zones', function (Blueprint $table) {
            $table->id();

            // Location identifiers
            $table->string('zip_code', 10)->index();
            $table->string('city', 64)->nullable();
            $table->string('state', 2)->nullable();

            // Service availability by day
            $table->boolean('service_monday')->default(false);
            $table->boolean('service_tuesday')->default(false);
            $table->boolean('service_wednesday')->default(false);
            $table->boolean('service_thursday')->default(false);
            $table->boolean('service_friday')->default(false);
            $table->boolean('service_saturday')->default(false);
            $table->boolean('service_sunday')->default(false);

            // Area assignment
            $table->string('area_code', 10)->nullable()->index();
            $table->json('driver_ids')->nullable()->comment('Array of driver user IDs');

            // Geographic data (for map-based validation)
            $table->json('polygon_coordinates')->nullable()->comment('GeoJSON polygon coordinates');
            $table->boolean('geo_enabled')->default(false);

            // Display and status
            $table->integer('display_order')->default(0);
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Unique constraint on zip code
            $table->unique('zip_code');

            // Indexes for performance
            $table->index(['active', 'area_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_zones');
    }
};

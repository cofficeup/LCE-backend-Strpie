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
        Schema::create('processing_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique(); // Internal code e.g. OAK-01

            // Availability
            $table->boolean('wash_fold_enabled')->default(true);
            $table->boolean('dry_clean_enabled')->default(true);
            $table->integer('daily_capacity_lbs')->default(1000);

            // Location
            $table->string('address_line1', 100);
            $table->string('address_line2', 100)->nullable();
            $table->string('city', 50);
            $table->string('state', 2);
            $table->string('zip_code', 10);

            // Routing Logic
            $table->string('served_area_codes', 1000)->nullable()->comment('Comma separated area codes');

            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Add site_id to pickups
        Schema::table('pickups', function (Blueprint $table) {
            $table->foreignId('processing_site_id')->nullable()->constrained('processing_sites')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropForeign(['processing_site_id']);
            $table->dropColumn('processing_site_id');
        });
        Schema::dropIfExists('processing_sites');
    }
};

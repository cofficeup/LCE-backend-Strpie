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
        Schema::create('pickup_holidays', function (Blueprint $table) {
            $table->id();

            // Holiday details
            $table->date('holiday_date')->index();
            $table->string('holiday_name', 100);

            // Area restriction (null = applies to all areas)
            $table->string('area_code', 10)->nullable()->index();

            // Status
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Composite index for efficient lookups
            $table->index(['holiday_date', 'area_code', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_holidays');
    }
};

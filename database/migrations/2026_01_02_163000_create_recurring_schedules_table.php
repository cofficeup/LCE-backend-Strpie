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
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');

            // Weekly schedule
            $table->boolean('schedule_monday')->default(false);
            $table->boolean('schedule_tuesday')->default(false);
            $table->boolean('schedule_wednesday')->default(false);
            $table->boolean('schedule_thursday')->default(false);
            $table->boolean('schedule_friday')->default(false);
            $table->boolean('schedule_saturday')->default(false);
            $table->boolean('schedule_sunday')->default(false);

            // Schedule details
            $table->enum('order_type', ['ppo', 'subscription'])->default('ppo');
            $table->integer('default_bags')->nullable(); // For subscription
            $table->decimal('default_weight', 8, 2)->nullable(); // For PPO estimate

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['active', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};

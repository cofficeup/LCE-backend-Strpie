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
        Schema::create('lce_user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->enum('type', ['welcome', 'promo', 'manual', 'refund'])->default('manual');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lce_user_credits');
    }
};

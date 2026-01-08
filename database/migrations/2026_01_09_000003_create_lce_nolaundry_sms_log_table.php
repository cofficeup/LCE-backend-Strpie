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
        Schema::create('lce_nolaundry_sms_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('pickup_id')->index();
            $table->dateTime('nolaundry_date');
            $table->string('phone_number', 32);
            $table->boolean('msg_sent')->default(false);
            $table->text('reply_text');
            $table->dateTime('reply_date')->nullable();
            $table->integer('status')->default(0);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('lce_user_info')->onDelete('cascade');
            $table->foreign('pickup_id')->references('id')->on('lce_user_pickup')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lce_nolaundry_sms_log');
    }
};

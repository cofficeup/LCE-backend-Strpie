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
        Schema::create('lce_user_group_log', function (Blueprint $table) {
            $table->increments('intid');
            $table->integer('user_id')->index();
            $table->integer('group_admin_id')->index();
            $table->integer('pickup_id')->index();
            $table->text('action');
            $table->text('note');
            $table->date('date_added');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('lce_user_info')->onDelete('cascade');
            $table->foreign('group_admin_id')->references('group_id')->on('lce_user_group_admin')->onDelete('cascade');
            $table->foreign('pickup_id')->references('id')->on('lce_user_pickup')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lce_user_group_log');
    }
};

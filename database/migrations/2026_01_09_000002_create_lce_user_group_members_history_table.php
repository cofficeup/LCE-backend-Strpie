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
        Schema::create('lce_user_group_members_history', function (Blueprint $table) {
            $table->increments('intid');
            $table->integer('group_id')->index();
            $table->integer('user_id')->index();
            $table->integer('group_admin_id')->index();
            $table->integer('invoice_id')->index();
            $table->date('transaction_date');
            $table->float('transaction_amount');
            $table->integer('wf_orders')->default(0);
            $table->integer('dc_orders')->default(0);
            $table->integer('wf_dc_orders')->default(0);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('lce_user_info')->onDelete('cascade');
            $table->foreign('group_admin_id')->references('group_id')->on('lce_user_group_admin')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('lce_user_invoice')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lce_user_group_members_history');
    }
};

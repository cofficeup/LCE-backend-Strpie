<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_tmp_nolaundry_transactions', function (Blueprint $table) {
            $table->increments('intid');
            $table->integer('pickup_id');
            $table->integer('user_id');
            $table->bigInteger('transaction_id');
            $table->dateTime('date_added');
            $table->string('status', 20);
            $table->string('type', 255);
            $table->float('amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_tmp_nolaundry_transactions');
    }
};

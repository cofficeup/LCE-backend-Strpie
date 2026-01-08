<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_holidays_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('holiday_date', 255);
            $table->text('email_ids');
            $table->timestamp('log_time')->useCurrent();
            $table->string('mail_sent', 20);
            $table->date('filter_date')->nullable();
            $table->integer('email_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_holidays_logs');
    }
};

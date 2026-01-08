<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_tmp_register_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('zip', 255);
            $table->string('email', 255);
            $table->date('cdate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_tmp_register_emails');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->text('value');
            $table->boolean('state')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_configurations');
    }
};

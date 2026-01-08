<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_tmp_group_members', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id');
            $table->string('email', 100);
            $table->decimal('monthly_transaction_limit', 10, 2);
            $table->boolean('monthly_wf_limit');
            $table->boolean('monthly_dc_limit');
            $table->string('department', 255);
            $table->date('cdate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_tmp_group_members');
    }
};

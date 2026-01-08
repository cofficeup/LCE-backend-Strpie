<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lce_tmp_invited_group_members_log', function (Blueprint $table) {
            $table->increments('id');
            $table->text('invited_emails');
            $table->text('restricted_emails');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_tmp_invited_group_members_log');
    }
};

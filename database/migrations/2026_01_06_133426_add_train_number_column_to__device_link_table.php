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
        Schema::table('DeviceLink', function (Blueprint $table) {
            // add train_number column
            $table->string('des_train_number')->nullable();
            $table->string('ori_train_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DeviceLink', function (Blueprint $table) {
            //
        });
    }
};

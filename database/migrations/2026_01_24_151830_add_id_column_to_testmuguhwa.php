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
        Schema::table('TestMuguhwa', function (Blueprint $table) {
            //
            $table->bigIncrements('id')->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TestMuguhwa', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('id');
        });
    }
};

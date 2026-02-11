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
        Schema::table('device_6h_analytics', function (Blueprint $table) {
            // Stores the count of 'OFF' signals in the last 3000 rows
            $table->integer('pir_off_count_3000')->default(0)->after('pir_on_count_6h');

            // Boolean flag for the final diagnostic (0 = OK, 1 = Issue)
            $table->tinyInteger('pir_issue')->default(0)->after('tof_issue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_6h_analytics', function (Blueprint $table) {
            $table->dropColumn(['pir_off_count_3000', 'pir_issue']);
        });
    }
};

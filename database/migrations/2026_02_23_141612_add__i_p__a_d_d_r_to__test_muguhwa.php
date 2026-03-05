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
        if (!Schema::hasColumn('TestMuguhwa', 'IP_ADDR')) {
            Schema::table('TestMuguhwa', function (Blueprint $table) {
                $table->string('IP_ADDR')->nullable()->after('TEMP');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TestMuguhwa', function (Blueprint $table) {
            $table->dropColumn('IP_ADDR');
        });
    }
};

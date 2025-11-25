<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TestMuguhwa', function (Blueprint $table) {
        
            // ✅ Create optimized index
            $table->index(['DEVICE', 'TIME'], 'idx_testmuguhwa_device_time');
        });
    }

    public function down(): void
    {
        Schema::table('TestMuguhwa', function (Blueprint $table) {
            // rollback
            $table->dropIndex('idx_testmuguhwa_device_time');
            $table->index('DEVICE');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('DeviceInfo2', function (Blueprint $table) {
            // ✅ Create new composite index
            $table->index(['DEVICE', 'INSTALL'], 'idx_deviceinfo2_device_install');
        });
    }

    public function down(): void
    {
        Schema::table('DeviceInfo2', function (Blueprint $table) {
            $table->dropIndex('idx_deviceinfo2_device_install');
            $table->index('DEVICE');
        });
    }
};
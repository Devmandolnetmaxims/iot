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
        Schema::table('DeviceInfo2', function (Blueprint $table) {
            // Adds created_at and updated_at columns
            $table->timestamps();

            // Adds deleted_at column for soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DeviceInfo2', function (Blueprint $table) {
            $table->dropTimestamps();   // Drops created_at and updated_at
            $table->dropSoftDeletes();  // Drops deleted_at
        });
    }
};

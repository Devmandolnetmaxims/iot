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
              // Add created_at
            if (!Schema::hasColumn('DeviceLink', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            // Add updated_at
            if (!Schema::hasColumn('DeviceLink', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

            // Add deleted_at
            if (!Schema::hasColumn('DeviceLink', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('DeviceLink', function (Blueprint $table) {
        //     $table->dropColumn(['created_at', 'updated_at', 'deleted_at']);
        // });
    }
};

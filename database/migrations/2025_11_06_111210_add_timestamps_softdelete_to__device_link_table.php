<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('DeviceLink', function (Blueprint $table) {
            // Make sure D_Link is primary or unique key
            $table->integer('D_Link')->autoIncrement()->primary()->change();

            // Add timestamps and soft deletes if not present
            if (!Schema::hasColumn('DeviceLink', 'created_at')) {
                $table->timestamps(); // created_at, updated_at
            }
            if (!Schema::hasColumn('DeviceLink', 'deleted_at')) {
                $table->softDeletes(); // deleted_at
            }
        });
    }

    public function down(): void
    {
        Schema::table('DeviceLink', function (Blueprint $table) {
            $table->dropPrimary(['D_Link']);
            $table->integer('D_Link')->change();
            $table->dropTimestamps();
            $table->dropSoftDeletes();
        });
    }
};


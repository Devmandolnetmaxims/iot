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

            if (!Schema::hasColumn('DeviceInfo2', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('TrainDep');
            }

            if (!Schema::hasColumn('DeviceInfo2', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }

            if (!Schema::hasColumn('DeviceInfo2', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('DeviceInfo2', function (Blueprint $table) {
        //     $table->dropColumn(['created_at', 'updated_at', 'deleted_at']);
        // });
    }
};

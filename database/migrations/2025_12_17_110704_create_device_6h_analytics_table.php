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
        Schema::create('device_6h_analytics', function (Blueprint $table) {
            $table->id();

            $table->dateTime('snapshot_time')->index();
            $table->dateTime('clock_time')->nullable()->index();
            $table->char('device', 50)->index();

            /* ---- FACT METRICS (FROM STAGING) ---- */
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('uv_on_count_6h')->default(0);
            $table->unsignedInteger('pir_on_count_6h')->default(0);
            $table->unsignedInteger('active_time_sum')->default(0);

            $table->unsignedInteger('temp_min')->nullable();
            $table->unsignedInteger('temp_max')->nullable();
            $table->unsignedInteger('temp_avg')->nullable();

            $table->unsignedInteger('tof_fault_count')->default(0);

            /* ---- LONG TERM UV LOGIC (FINAL RESULT FLAGS) ---- */
            $table->boolean('uv_pass_30')->default(false);
            $table->boolean('uv_pass_200')->default(false);
            $table->boolean('uv_pass_1000')->default(false);

            /* ---- OTHER CONDITIONS ---- */
            $table->boolean('network_missing')->default(false);
            $table->boolean('temp_issue')->default(false);
            $table->boolean('tof_issue')->default(false);

            /* ---- FINAL DECISION ---- */
            $table->string('final_color', 20)->index();
            $table->string('decision_reason')->nullable();

            $table->timestamps();

            $table->unique(['snapshot_time', 'device']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_6h_analytics');
    }
};

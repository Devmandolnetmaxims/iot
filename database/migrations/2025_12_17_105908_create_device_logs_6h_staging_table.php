<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_logs_6h_staging', function (Blueprint $table) {
            $table->id();

            $table->char('device', 50)->index();
            $table->dateTime('time')->index();

            $table->unsignedInteger('begin');
            $table->unsignedInteger('last');
            $table->char('event', 1)->nullable();

            $table->unsignedInteger('active');
            $table->char('pir', 3)->default('OFF');
            $table->char('tof', 3)->default('IN');
            $table->char('uv', 3)->default('OFF');

            $table->unsignedInteger('mm')->nullable();
            $table->unsignedInteger('temp')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs_6h_staging');
    }
};

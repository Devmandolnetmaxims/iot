<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('device_current_color', function (Blueprint $table) {
            $table->char('device', 50)->primary()->index();
            $table->string('color', 20);
            $table->dateTime('snapshot_time');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_current_color');
    }
};

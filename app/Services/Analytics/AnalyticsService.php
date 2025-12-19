<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function __construct()
    {
        //
    }

    public static function Load6hrawdata($request = null)
    {
        Log::info('Load6hrawdata started');

        // Safely extract time
        if ($request->time) {
            $time = $request->time;
            Log::info('Time received from request', ['time' => $time]);
        } else {
            $time = null;
            Log::info('No time provided, using current time');
        }

        // 1️⃣ Determine snapshot time
        $snapshotTime = $time
            ? Carbon::parse($time, 'Asia/Kolkata')
            : Carbon::now('Asia/Kolkata');

        Log::info('Snapshot time determined', [
            'snapshot_time' => $snapshotTime->toDateTimeString(),
        ]);

        // Align to 6-hour boundary
        $snapshotHour = floor($snapshotTime->hour / 6) * 6;

        $snapshotTime
            ->setHour($snapshotHour)
            ->setMinute(0)
            ->setSecond(0);

        $from = $snapshotTime->copy()->subHours(6);
        $to   = $snapshotTime;

        Log::info('6-hour window calculated', [
            'from' => $from->toDateTimeString(),
            'to'   => $to->toDateTimeString(),
        ]);

        try {
            // 2️⃣ Clear staging table
            DB::statement('TRUNCATE TABLE device_logs_6h_staging');
            Log::info('Staging table truncated');

            // 3️⃣ Insert last 6 hours data
            DB::statement("
                INSERT INTO device_logs_6h_staging
                (
                    device, time, begin, last, event,
                    active, pir, tof, uv, mm, temp,
                    created_at, updated_at
                )
                SELECT
                    device, time, begin, last, event,
                    active, pir, tof, uv, mm, temp,
                    NOW(), NOW()
                FROM testmuguhwa
                WHERE time >= ? AND time < ?
            ", [$from, $to]);

            $count = DB::table('device_logs_6h_staging')->count();

            Log::info('Load6hrawdata completed successfully', [
                'rows_inserted' => $count,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('Load6hrawdata failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

 }

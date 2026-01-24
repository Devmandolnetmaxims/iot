<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    // Load 6 hourse raw data in temp table
    // public static function Load6hrawdata($request = null)
    // {
    //     Log::info('Load6hrawdata started');

    //     // Safely extract time
    //     if ($request->time) {
    //         $time = $request->time;
    //         Log::info('Time received from request', ['time' => $time]);
    //     } else {
    //         $time = null;
    //         Log::info('No time provided, using current time');
    //     }

    //     // 1 Determine snapshot time
    //     $snapshotTime = $time
    //         ? Carbon::parse($time, 'Asia/Kolkata')
    //         : Carbon::now('Asia/Kolkata');

    //     Log::info('Snapshot time determined', [
    //         'snapshot_time' => $snapshotTime->toDateTimeString(),
    //     ]);

    //     // Align to 6-hour boundary
    //     $snapshotHour = floor($snapshotTime->hour / 6) * 6;

    //     $snapshotTime
    //         ->setHour($snapshotHour)
    //         ->setMinute(0)
    //         ->setSecond(0);

    //     $from = $snapshotTime->copy()->subHours(6);
    //     $to   = $snapshotTime;

    //     Log::info('6-hour window calculated', [
    //         'from' => $from->toDateTimeString(),
    //         'to'   => $to->toDateTimeString(),
    //     ]);

    //     try {
    //         // 2 Check if data already exists
    //         $exists = DB::table('device_logs_6h_staging')
    //             ->where('time', '>=', $from)
    //             ->where('time', '<', $to)
    //             ->exists();

    //         if ($exists) {
    //             Log::info('Skipping load '.$from.' – '.$to.' data already present');
    //             return true;
    //         }

    //         // 3 Clear staging table
    //         DB::statement('TRUNCATE TABLE device_logs_6h_staging');
    //         Log::info('Staging table truncated');

    //         // 4 Insert last 6 hours data
    //         DB::statement("
    //             INSERT INTO device_logs_6h_staging
    //             (
    //                 device, time, begin, last, event,
    //                 active, pir, tof, uv, mm, temp,
    //                 created_at, updated_at
    //             )
    //             SELECT
    //                 device, time, begin, last, event,
    //                 active, pir, tof, uv, mm, temp,
    //                 NOW(), NOW()
    //             FROM TestMuguhwa
    //             WHERE time >= ? AND time < ?
    //         ", [$from, $to]);

    //         $count = DB::table('device_logs_6h_staging')->count();

    //         Log::info('Load6hrawdata completed successfully', [
    //             'rows_inserted' => $count,
    //         ]);

    //         // AnalyticsService::CalculatErrorState();
    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('Load6hrawdata failed', [
    //             'message' => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);

    //         return false;
    //     }
    // }  // working

    // public static function Load6hrawdata($request = null)
    // {
    //     Log::info('Load6hrawdata started (Cross-DB Sync)');

    //     // Safely extract time
    //     if ($request->time) {
    //         $time = $request->time;
    //         Log::info('Time received from request', ['time' => $time]);
    //     } else {
    //         $time = null;
    //         Log::info('No time provided, using current time');
    //     }
    //     // 1. Determine time window (Keep your existing logic)
    //     $time = $request && isset($request->time) ? $request->time : null;
    //     $snapshotTime = $time ? Carbon::parse($time, 'Asia/Kolkata') : Carbon::now('Asia/Kolkata');
    //     $snapshotHour = floor($snapshotTime->hour / 6) * 6;
    //     $snapshotTime->setTime($snapshotHour, 0, 0);

    //     $from = $snapshotTime->copy()->subHours(6);
    //     $to   = $snapshotTime;

    //     try {
    //         // 2 Check if data already exists
    //         $exists = DB::table('device_logs_6h_staging')
    //             ->where('time', '>=', $from)
    //             ->where('time', '<', $to)
    //             ->exists();

    //         if ($exists) {
    //             Log::info('Skipping load '.$from.' – '.$to.' data already present');
    //             return true;
    //         }

    //         DB::table('device_logs_6h_staging')->truncate();

    //         // Increase chunk size to 5000 to reduce network "chatter"
    //         // DB::connection('external_db')->table('TestMuguhwa')
    //         //     ->where('TIME', '>=', $from)
    //         //     ->where('TIME', '<', $to)
    //         //     ->orderBy('TIME')
    //         //     ->chunk(5000, function ($rows) {
    //         //         // Convert collection to array and insert directly
    //         //         $data = json_decode(json_encode($rows), true);

    //         //         // Add timestamps manually if not in external DB
    //         //         $now = now();
    //         //         foreach($data as &$row) {
    //         //             $row['created_at'] = $now;
    //         //             $row['updated_at'] = $now;
    //         //         }

    //         //         DB::table('device_logs_6h_staging')->insert($data);
    //         //     });

    //         $rows = DB::connection('external_db')->table('TestMuguhwa')
    //         ->where('TIME', '>=', $from)
    //         ->where('TIME', '<', $to)
    //         ->orderBy('TIME')
    //         ->limit(100) // Get exactly 1000
    //         ->get();

    //         // Convert and add timestamps
    //         $now = now();
    //         $data = $rows->map(function ($row) use ($now) {
    //             $array = (array) $row;
    //             $array['created_at'] = $now;
    //             $array['updated_at'] = $now;
    //             return $array;
    //         })->toArray();

    //         DB::table('device_logs_6h_staging')->insert($data);

    //         $rows = DB::connection('external_db2')->table('TestMuguhwa')
    //         ->where('TIME', '>=', $from)
    //         ->where('TIME', '<', $to)
    //         ->orderBy('TIME')
    //         ->limit(100) // Get exactly 1000
    //         ->get();

    //         // Convert and add timestamps
    //         $now = now();
    //         $data = $rows->map(function ($row) use ($now) {
    //             $array = (array) $row;
    //             $array['created_at'] = $now;
    //             $array['updated_at'] = $now;
    //             return $array;
    //         })->toArray();

    //         DB::table('device_logs_6h_staging')->insert($data);
    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('Sync failed: ' . $e->getMessage());
    //         return false;
    //     }
    // }  //for external db

    public static function Load6hrawdata($request = null)
    {
        Log::info('--- Load6hrawdata Sync Started ---');

        // 1. Determine time window
        $time = $request && isset($request->time) ? $request->time : null;
        try {
            $snapshotTime = $time ? Carbon::parse($time, 'Asia/Kolkata') : Carbon::now('Asia/Kolkata');
        } catch (\Exception $e) {
            Log::error('Invalid time format provided', ['input' => $time]);
            return false;
        }

        $snapshotHour = floor($snapshotTime->hour / 6) * 6;
        $snapshotTime->setTime($snapshotHour, 0, 0);

        $from = $snapshotTime->copy()->subHours(6);
        $to   = $snapshotTime;

        Log::info("Time Range: [{$from}] to [{$to}]");

        try {
            // 2. Check if data already exists
            $exists = DB::table('device_logs_6h_staging')
                ->where('time', '>=', $from)
                ->where('time', '<', $to)
                ->exists();

            if ($exists) {
                Log::info('Sync Skipped: Data already present for this time range.');
                return true;
            }

            // 3. Clear staging table
            DB::table('device_logs_6h_staging')->truncate();
            Log::info('Staging table truncated.');

            // 4. List of connections to pull from
            $externalConnections = ['external_db', 'external_db2'];
            $totalInserted = 0;
            $now = now();

            foreach ($externalConnections as $connection) {
                Log::info("Fetching data from connection: {$connection}");

                $rows = DB::connection($connection)->table('TestMuguhwa')
                    ->where('TIME', '>=', $from)
                    ->where('TIME', '<', $to)
                    ->orderBy('TIME')
                    ->limit(1000) // Changed to 1000 as per your request
                    ->get();

                $count = $rows->count();
                Log::info("Found {$count} rows in {$connection}");

                if ($count > 0) {
                    $data = $rows->map(function ($row) use ($now) {
                        $array = (array) $row;
                        $array['created_at'] = $now;
                        $array['updated_at'] = $now;
                        return $array;
                    })->toArray();

                    DB::table('device_logs_6h_staging')->insert($data);
                    $totalInserted += $count;
                    Log::info("Successfully inserted {$count} rows from {$connection}");
                }
            }

            Log::info("--- Load6hrawdata Completed. Total Rows: {$totalInserted} ---");
            return true;

        } catch (\Throwable $e) {
            Log::error('Sync failed with error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    // Calculate error state
    public static function CalculatErrorState($request = null)
    {
        $currentDate = now()->toDateTimeString();
        // Check network issue. If within 6 hourse if device not sending any logs then we count it as offline or network issue.
        Log::info('Calculate6hAnalytics started');
        if(self::NetworkIssue()) {
            Log::info('NetworkIssue completed successfully');
        }
        if(self::TOFIssue()) {
            Log::info('TOFIssue completed successfully');
        }
        if(self::TempIssue()) {
            Log::info('TempIssue completed successfully');
        }
        if(self::UVCheckForLast30()) {
            Log::info('UVCheckForLast30 completed successfully');
        }
        if(self::UVCheckForLast200()) {
            Log::info('UVCheckForLast200 completed successfully');
        }
        if(self::UVCheckForLast1000()) {
            Log::info('UVCheckForLast1000 completed successfully');
        }
        if(self::UVFailLast1000()) {
            Log::info('UVFailLast1000 completed successfully');
        }

        if(self::UVCheckPersistent3Days($currentDate)) {
            Log::info('UVCheckPersistent3Days completed successfully');
        }

        self::ResolveFinalState($currentDate);
        Log::info('Calculate6hAnalytics completed');
    }

    // Check network issue
    private static function NetworkIssue()
    {
        try {
            Log::info('NetworkIssue started');

            /*
            |------------------------------------------------------
            | 1. Identify window based on staging table MAX(time)
            |------------------------------------------------------
            | This creates a FIXED 6h branch for ALL devices
            */
            $windowStart = DB::table('device_logs_6h_staging')
                ->selectRaw("
                    CASE
                        WHEN HOUR(MAX(time)) < 6  THEN DATE(MAX(time)) + INTERVAL 0 HOUR
                        WHEN HOUR(MAX(time)) < 12 THEN DATE(MAX(time)) + INTERVAL 6 HOUR
                        WHEN HOUR(MAX(time)) < 18 THEN DATE(MAX(time)) + INTERVAL 12 HOUR
                        ELSE DATE(MAX(time)) + INTERVAL 18 HOUR
                    END AS window_start
                ")
                ->value('window_start');

            // No data at all
            if (!$windowStart) {
                Log::warning('NetworkIssue: No staging data found');
                return false;
            }

            $windowStart = Carbon::parse($windowStart);
            $windowEnd   = $windowStart->copy()->addHours(6);

            $clockTime = $windowStart->format('Y-m-d H:i:s');

            // 🚫 Prevent duplicate processing for same 6h window
            $alreadyProcessed = DB::table('device_6h_analytics')
                ->where('clock_time', $clockTime)
                ->exists();

            if ($alreadyProcessed) {
                Log::warning('NetworkIssue skipped – already processed for this window', [
                    'clock_time' => $clockTime,
                ]);

                return false; // or true, depending on how you treat "already done"
            }

            Log::info('6h Window Identified', [
                'clock_time' => $clockTime,
                'from'       => $windowStart->toDateTimeString(),
                'to'         => $windowEnd->toDateTimeString(),
            ]);

            /*
            |------------------------------------------------------
            | 2. Insert analytics for THIS EXACT window
            |------------------------------------------------------
            | - LEFT JOIN ensures BLUE for missing devices
            | - clock_time is the branch identifier
            */
            DB::statement("
                INSERT INTO device_6h_analytics (
                    snapshot_time,
                    clock_time,
                    device,
                    total_records,
                    network_missing,
                    final_color,
                    decision_reason,
                    uv_on_count_6h,
                    pir_on_count_6h,
                    temp_min,
                    temp_max,
                    temp_avg,
                    created_at,
                    updated_at
                )
                SELECT
                    NOW(),
                    '{$clockTime}',
                    m.DEVICE,
                    COUNT(s.device),
                    CASE WHEN COUNT(s.device) = 0 THEN 1 ELSE 0 END,
                    CASE WHEN COUNT(s.device) = 0 THEN 'BLUE' ELSE 'GREEN' END,
                    CASE WHEN COUNT(s.device) = 0 THEN 'No data in this 6h window' ELSE NULL END,
                    SUM(CASE WHEN s.uv = 'ON' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN s.pir = 'ON' THEN 1 ELSE 0 END),
                    MIN(s.temp),
                    MAX(s.temp),
                    AVG(s.temp),
                    NOW(),
                    NOW()
                FROM DeviceInfo2 m
                LEFT JOIN device_logs_6h_staging s
                    ON s.device = m.DEVICE
                    AND s.time >= ?
                    AND s.time <  ?
                GROUP BY m.DEVICE
            ", [
                $windowStart->format('Y-m-d H:i:s'),
                $windowEnd->format('Y-m-d H:i:s'),
            ]);

            Log::info('NetworkIssue completed successfully', [
                'clock_time' => $clockTime
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('NetworkIssue failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    // Check TOF issue
    private static function TOFIssue()
    {
        DB::statement("
            UPDATE device_6h_analytics a

            /* latest snapshot time */
            JOIN (
                SELECT MAX(snapshot_time) AS max_snapshot_time
                FROM device_6h_analytics
            ) mx ON a.snapshot_time = mx.max_snapshot_time

            /* latest mm per device */
            JOIN (
                SELECT s1.device, s1.mm AS latest_mm
                FROM device_logs_6h_staging s1
                JOIN (
                    SELECT device, MAX(time) AS max_time
                    FROM device_logs_6h_staging
                    GROUP BY device
                ) s2
                    ON s2.device = s1.device
                AND s2.max_time = s1.time
            ) lm ON lm.device = a.device

            /* TOF fault count per device */
            JOIN (
                SELECT device, SUM(mm BETWEEN 1 AND 30) AS tof_fault_count
                FROM device_logs_6h_staging
                GROUP BY device
            ) tc ON tc.device = a.device

            SET
                a.tof_fault_count = tc.tof_fault_count,

                a.tof_issue = CASE
                    WHEN lm.latest_mm BETWEEN 1 AND 30 THEN 1
                    ELSE 0
                END,

                a.decision_reason = CASE
                    WHEN a.network_missing = 1 THEN a.decision_reason
                    WHEN lm.latest_mm BETWEEN 1 AND 30
                        THEN 'TOF sensor fault (latest mm in 1–30 range)'
                    ELSE a.decision_reason
                END,

                a.updated_at = NOW()
        ");
    }


    // Check temp issue
    private static function TempIssue() {
        DB::statement("
            UPDATE device_6h_analytics a
            JOIN (
                SELECT s.device, s.temp
                FROM device_logs_6h_staging s
                INNER JOIN (
                    SELECT device, MAX(time) AS last_time
                    FROM device_logs_6h_staging
                    GROUP BY device
                ) x ON x.device = s.device AND x.last_time = s.time
            ) last_row ON last_row.device = a.device
            SET
                a.temp_issue = CASE
                    WHEN last_row.temp < 720 THEN 1
                    ELSE 0
                END,
                a.decision_reason = CASE
                    WHEN last_row.temp < 720 AND a.network_missing = 0
                        THEN 'Temperature below threshold (<720)'
                    ELSE a.decision_reason
                END
        ");
    }

    private static function UVCheckForLast30(){
        DB::statement("
            UPDATE device_6h_analytics a
            JOIN (
                SELECT DISTINCT device
                FROM (
                    SELECT
                        device,
                        uv,
                        @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
                        @prev_device := device
                    FROM device_logs_6h_staging
                    CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
                    ORDER BY device, time DESC
                ) ranked
                WHERE rn <= 30
                AND TRIM(UPPER(uv)) = 'ON'
            ) u ON TRIM(u.device) = TRIM(a.device)
            SET
                a.uv_pass_30 = 1,
                a.decision_reason = 'UV ON detected in last 30 records'
        ");
    }

    private static function UVCheckForLast200()
    {
        DB::statement("UPDATE device_6h_analytics a
            SET
                a.uv_pass_200 = 1,
                a.decision_reason = 'UV ON detected in last 200 records'
            WHERE
                (a.final_color IS NULL OR a.final_color = 'GREEN')
                AND EXISTS (
                    SELECT 1
                    FROM (
                        SELECT
                            device,
                            uv,
                            @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
                            @prev_device := device
                        FROM device_logs_6h_staging
                        CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
                        ORDER BY device, time DESC
                    ) ranked
                    WHERE
                        ranked.device = a.device
                        AND ranked.rn <= 200
                        AND TRIM(UPPER(ranked.uv)) = 'ON'
                );"
            );
    }

    private static function UVCheckForLast1000()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            SET
                a.uv_pass_1000 = 1,
                a.decision_reason = 'UV ON detected in last 1000 records'
            WHERE
                (a.final_color IS NULL OR a.final_color = 'GREEN')
                AND EXISTS (
                    SELECT 1
                    FROM (
                        SELECT
                            device,
                            uv,
                            @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
                            @prev_device := device
                        FROM device_logs_6h_staging
                        CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
                        ORDER BY device, time DESC
                    ) ranked
                    WHERE
                        ranked.device = a.device
                        AND ranked.rn <= 1000
                        AND TRIM(UPPER(ranked.uv)) = 'ON'
                )
        ");
    }

    private static function UVFailLast1000()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            SET
                a.uv_pass_1000 = 0,
                a.decision_reason = 'No UV ON detected in last 1000 records – manual attention required'
            WHERE
                (a.final_color IS NULL OR a.final_color = 'GREEN')
                AND NOT EXISTS (
                    SELECT 1
                    FROM (
                        SELECT
                            device,
                            uv,
                            @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
                            @prev_device := device
                        FROM device_logs_6h_staging
                        CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
                        ORDER BY device, time DESC
                    ) ranked
                    WHERE
                        ranked.device = a.device
                        AND ranked.rn <= 1000
                        AND TRIM(UPPER(ranked.uv)) = 'ON'
                )
        ");
    }

    // private static function UVCheckPersistent3Days($currentDate)
    // {
    //     // 1. Define our time boundaries
    //     $threeDaysAgoStart = Carbon::today()->subDays(3)->toDateTimeString(); // Start of 3 days ago
    //     // $todayStart = Carbon::today()->toDateTimeString();                   // Start of today
    //     $todayStart = $currentDate;                   // Start of today
    //     // dd($todayStart, $threeDaysAgoStart);
    //     // 2. Identify devices that had network_missing = 1 consistently over the last 3 days
    //     // We use a subquery to find these specific device IDs
    //     $failingDevices = DB::table('device_6h_analytics')
    //         ->select('device')
    //         ->where('clock_time', '>=', $threeDaysAgoStart)
    //         ->where('clock_time', '<', $todayStart)
    //         ->where('network_missing', 1)
    //         ->groupBy('device')
    //         // Ensures the failure exists across 3 distinct dates
    //         ->havingRaw('COUNT(DISTINCT DATE(clock_time)) >= 3')
    //         ->pluck('device');

    //     // 3. If any devices match, update their records for "today"
    //     if ($failingDevices->isNotEmpty()) {
    //         DB::table('device_6h_analytics')
    //             ->whereIn('device', $failingDevices)
    //             ->where('created_at', '>=', $todayStart)
    //             ->update([
    //                 'uv_persistent_fail' => 1,
    //                 'final_color' => 'CYAN',
    //                 'updated_at' => now()
    //             ]);
    //     }
    // }

    private static function UVCheckPersistent3Days($currentDate)
    {
        $threeDaysAgo = Carbon::parse($currentDate)->subDays(2)->startOfDay()->toDateTimeString();

        $failingDevices = DB::table('device_6h_analytics')
            ->select('device')
            ->where('clock_time', '>=', $threeDaysAgo)
            ->where('clock_time', '<', $currentDate)
            ->where('network_missing', 1)
            ->groupBy('device')
            // Logic: Device must have missing records across 3 distinct calendar dates
            ->havingRaw('COUNT(DISTINCT DATE(clock_time)) >= 2')
            ->pluck('device');

        if ($failingDevices->isNotEmpty()) {
            DB::table('device_6h_analytics')
                ->whereIn('device', $failingDevices)
                /* STRICT LOCK: Only update the specific record you are processing */
                ->where('snapshot_time', $currentDate)
                ->update([
                    'uv_persistent_fail' => 1,
                    'updated_at' => now()
                ]);
        }
    }

    // private static function ResolveFinalState($currentDate)
    // {
    //     DB::statement("
    //         UPDATE device_6h_analytics
    //         SET
    //             final_color = CASE
    //                 -- 💎 Cyan: 3-Day Persistent UV Failure (High Priority)
    //                 WHEN uv_persistent_fail = 1 THEN 'CYAN'

    //                 -- 🔵 Network issue
    //                 WHEN network_missing = 1 THEN 'BLUE'

    //                 -- 🟡 TOF issue (locks state)
    //                 WHEN tof_issue = 1 THEN 'YELLOW'

    //                 -- 🟠 Temperature issue
    //                 WHEN temp_issue = 1 THEN 'ORANGE'

    //                 -- 🟢 UV OK if ANY window passes
    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0 THEN 'GREEN'

    //                 -- 🩷 UV completely OFF
    //                 ELSE 'MAGENTA'
    //             END,

    //             decision_reason = CASE
    //                 WHEN network_missing = 1
    //                     THEN 'No data in this 6h window'

    //                 WHEN uv_persistent_fail = 1
    //                     THEN 'Critical : Device is faling for 3 days'

    //                 WHEN tof_issue = 1
    //                     THEN 'TOF issue detected'

    //                 WHEN temp_issue = 1
    //                     THEN 'Temperature below threshold'

    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0
    //                     THEN 'UV ON detected in recent history'

    //                 ELSE 'UV OFF in 30/200/1000 records'
    //             END
    //     ");
    // }
    private static function ResolveFinalState($currentDate)
    {
        // Ensure the date is in the correct string format for the SQL query
        $snapshotTime = $currentDate;

        DB::statement("
            UPDATE device_6h_analytics
            SET
                final_color = CASE
                    /* 💎 CYAN: Check this FIRST. If the 3-day check found a failure,
                    it must override the standard BLUE status. */
                    WHEN uv_persistent_fail = 1 THEN 'CYAN'

                    /* 🔵 BLUE: Standard network missing for this specific slot */
                    WHEN network_missing = 1 THEN 'BLUE'

                    /* 🟡 YELLOW: TOF issue */
                    WHEN tof_issue = 1 THEN 'YELLOW'

                    /* 🟠 ORANGE: Temp issue */
                    WHEN temp_issue = 1 THEN 'ORANGE'

                    /* 🟢 GREEN: Successful UV ON */
                    WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0 THEN 'GREEN'

                    /* 🩷 MAGENTA: UV is simply OFF but network is fine */
                    ELSE 'MAGENTA'
                END,

                decision_reason = CASE
                    WHEN uv_persistent_fail = 1 THEN 'Critical : Device is failing for 3 days'
                    WHEN network_missing = 1 THEN 'No data in this 6h window'
                    WHEN tof_issue = 1 THEN 'TOF issue detected'
                    WHEN temp_issue = 1 THEN 'Temperature below threshold'
                    WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0 THEN 'UV ON detected in recent history'
                    ELSE 'UV OFF in 30/200/1000 records'
                END
            /* THE FIX: This WHERE clause ensures that only the data for
            the current date/time you are processing gets updated.
            */
            WHERE snapshot_time = '{$snapshotTime}'
        ");
    }

}

<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    // Load 6 hourse raw data in temp table
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

        // 1 Determine snapshot time
        $snapshotTime = $time
            ? Carbon::parse($time, 'Asia/Seoul')
            : Carbon::now('Asia/Seoul');

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
            // 2 Check if data already exists
            $exists = DB::table('device_logs_6h_staging')
                ->where('time', '>=', $from)
                ->where('time', '<', $to)
                ->exists();

            if ($exists) {
                Log::info('Skipping load '.$from.' – '.$to.' data already present');
                return true;
            }

            // 3 Clear staging table
            DB::statement('TRUNCATE TABLE device_logs_6h_staging');
            Log::info('Staging table truncated');

            // 4 Insert last 6 hours data
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
                FROM TestMuguhwa
                WHERE time >= ? AND time < ?
            ", [$from, $to]);

            $count = DB::table('device_logs_6h_staging')->count();

            Log::info('Load6hrawdata completed successfully', [
                'rows_inserted' => $count,
            ]);

            $request->time = $from;

            AnalyticsService::CalculatErrorState($request);
            AnalyticsService::CheckDeviceDiagnostics($request);


            // AnalyticsService::CheckPersistent3Days($request);
            // AnalyticsService::ThreeDaysNetworkAnalytics($request);
            return true;

        } catch (\Throwable $e) {
            Log::error('Load6hrawdata failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }  // it working

    public static function Run6hrawdata($request = null){
         try {
                Log::info('LoadHourlyData: Command started');

                Artisan::call('app:load6h-raw-data');

                $output = Artisan::output();

                Log::info('Load6HourData: Command finished', [
                    'output' => $output
                ]);

                return $output;
        } catch (\Exception $e) {

            Log::error('LoadHourlyData: Command failed', [
                'error' => $e->getMessage()
            ]);

            return $e->getMessage();
        }
    }

    public static function Load1hrawdata($request = null)
    {
        $time = $request && isset($request->time) ? $request->time : null;
        try {
            $nowTime = $time ? Carbon::parse($time, 'Asia/Seoul') : Carbon::now('Asia/Seoul');
        } catch (\Exception $e) {
            Log::error('Invalid time format', ['input' => $time]);
            return false;
        }

        $carbonFrom = $nowTime->copy()->startOfHour(); // 13:00:00
        $carbonTo   = $carbonFrom->copy()->addHour();    // 14:00:00

        $toString = $carbonTo->format('Y-m-d H:i:s');
        $fromStr = $carbonFrom->format('Y-m-d H:i:s');

        Log::info("Target Window: [{$fromStr}] to [{$toString}]");

        // 1. DUPLICATE CHECK
        $exists = DB::table('TestMuguhwa')
            ->where('TIME', '>=', $fromStr)
            ->where('TIME', '<', $toString)
            ->exists();

        if ($exists) {
            Log::info("Sync Skipped: Data for {$fromStr} already exists.");
            return true;
        }

        $externalConnections = [
            'external_db'  => config('database.connections.external_db'),
            'external_db2' => config('database.connections.external_db2'),
        ];

        // Local DB Credentials
        $localUser = config('database.connections.mysql.username');
        $localPass = config('database.connections.mysql.password');
        $localDb   = config('database.connections.mysql.database');

        foreach ($externalConnections as $name => $conf) {
            Log::info("Starting fast dump for: {$name}");

            // Build the command exactly like your working reference
            // We use single quotes for passwords to handle special characters safely
            $command = sprintf(
                "mysqldump -h %s -u %s -p'%s' --no-tablespaces %s TestMuguhwa " .
                "--where=\"TIME >= '%s' AND TIME < '%s'\" " .
                "--no-create-info --single-transaction --quick --skip-extended-insert --compact" .
                "| sed 's/INSERT INTO `TestMuguhwa` VALUES/INSERT INTO `TestMuguhwa` (`DEVICE`, `TIME`, `BEGIN`, `LAST`, `EVENT`, `ACTIVE`, `PIR`, `TOF`, `UV`, `MM`, `TEMP`) VALUES/' " .
                "| mysql -u %s -p'%s' %s",
                $conf['host'], $conf['username'], $conf['password'], $conf['database'],
                $fromStr, $toString,
                $localUser, $localPass, $localDb
            );

            exec($command, $output, $resultCode);

            if ($resultCode !== 0) {
                Log::error("Dump failed for {$name}", ['resultCode' => $resultCode]);
            } else {
                Log::info("Successfully piped and mapped data from {$name}");
            }
        }

        return true;
    }

    public static function RunLoadHourlyDataWithLog($request = null)
    {
        try {
            if($request->input('token') == '9f3c8a7d1e4b5a2c6d8f9e0b7a1c4e2f') {
                Log::info('LoadHourlyData: Command started');

                Artisan::call('app:load-hourly-data');

                $output = Artisan::output();

                Log::info('LoadHourlyData: Command finished', [
                    'output' => $output
                ]);

                return $output;
            } else {
                return false;
            }


        } catch (\Exception $e) {

            Log::error('LoadHourlyData: Command failed', [
                'error' => $e->getMessage()
            ]);

            return $e->getMessage();
        }
    }

    // Calculate error state
    // public static function CalculatErrorState($request = null)
    // {
    //     $currentDate = now()->toDateTimeString();
    //     // Check network issue. If within 6 hourse if device not sending any logs then we count it as offline or network issue.
    //     Log::info('Calculate6hAnalytics started');
    //     if(self::NetworkIssue()) {
    //         Log::info('NetworkIssue completed successfully');
    //     }
    //     if(self::TOFIssue()) {
    //         Log::info('TOFIssue completed successfully');
    //     }
    //     if(self::TempIssue()) {
    //         Log::info('TempIssue completed successfully');
    //     }
    //     if(self::UVCheckForLast30()) {
    //         Log::info('UVCheckForLast30 completed successfully');
    //     }
    //     if(self::UVCheckForLast200()) {
    //         Log::info('UVCheckForLast200 completed successfully');
    //     }
    //     if(self::UVCheckForLast1000()) {
    //         Log::info('UVCheckForLast1000 completed successfully');
    //     }
    //     if(self::UVFailLast1000()) {
    //         Log::info('UVFailLast1000 completed successfully');
    //     }

    //     if(self::CheckPirStatus()) {
    //         Log::info('CheckPirStatus completed successfully');
    //     }

    //     self::ResolveFinalState($currentDate);
    //     Log::info('Calculate6hAnalytics completed');
    // }

    public static function CalculatErrorState($request = null)
    {
        Log::info('Calculate6hAnalytics started');

        // Extract the window start time from the request
        // This is the $from value you set in Load6hrawdata
        $clockTime = $request->time ?? now()->toDateTimeString();

        // 1. Pass $clockTime into NetworkIssue.
        // It should use this time to create the record if it doesn't exist.
        if(self::NetworkIssue($clockTime)) {
            Log::info("NetworkIssue processed for: $clockTime");
        }

        // 2. Pass $clockTime to every sub-function to target ONLY those rows
        if(self::TOFIssue($clockTime)) {
            Log::info('TOFIssue completed successfully');
        }

        if(self::TempIssue($clockTime)) {
            Log::info('TempIssue completed successfully');
        }

        if(self::UVCheckForLast30($clockTime)) {
            Log::info('UVCheckForLast30 completed successfully');
        }

        if(self::UVCheckForLast200($clockTime)) {
            Log::info('UVCheckForLast200 completed successfully');
        }

        if(self::UVCheckForLast1000($clockTime)) {
            Log::info('UVCheckForLast1000 completed successfully');
        }

        if(self::UVFailLast1000($clockTime)) {
            Log::info('UVFailLast1000 completed successfully');
        }

        // if(self::CheckPirStatus($clockTime)) {
        //     Log::info('CheckPirStatus completed successfully');
        // }

        // 3. Resolve using the same clock time
        self::ResolveFinalState($clockTime);

        Log::info('Calculate6hAnalytics completed');
    }

    // Check network issue
    // private static function NetworkIssue()
    // {
    //     try {
    //         Log::info('NetworkIssue started');

    //         /*
    //         |------------------------------------------------------
    //         | 1. Identify window based on staging table MAX(time)
    //         |------------------------------------------------------
    //         | This creates a FIXED 6h branch for ALL devices
    //         */
    //         $windowStart = DB::table('device_logs_6h_staging')
    //             ->selectRaw("
    //                 CASE
    //                     WHEN HOUR(MAX(time)) < 6  THEN DATE(MAX(time)) + INTERVAL 0 HOUR
    //                     WHEN HOUR(MAX(time)) < 12 THEN DATE(MAX(time)) + INTERVAL 6 HOUR
    //                     WHEN HOUR(MAX(time)) < 18 THEN DATE(MAX(time)) + INTERVAL 12 HOUR
    //                     ELSE DATE(MAX(time)) + INTERVAL 18 HOUR
    //                 END AS window_start
    //             ")
    //             ->value('window_start');

    //         // No data at all
    //         if (!$windowStart) {
    //             Log::warning('NetworkIssue: No staging data found');
    //             return false;
    //         }

    //         $windowStart = Carbon::parse($windowStart);
    //         $windowEnd   = $windowStart->copy()->addHours(6);

    //         $clockTime = $windowStart->format('Y-m-d H:i:s');

    //         // 🚫 Prevent duplicate processing for same 6h window
    //         $alreadyProcessed = DB::table('device_6h_analytics')
    //             ->where('clock_time', $clockTime)
    //             ->exists();

    //         if ($alreadyProcessed) {
    //             Log::warning('NetworkIssue skipped – already processed for this window', [
    //                 'clock_time' => $clockTime,
    //             ]);

    //             return false; // or true, depending on how you treat "already done"
    //         }

    //         Log::info('6h Window Identified', [
    //             'clock_time' => $clockTime,
    //             'from'       => $windowStart->toDateTimeString(),
    //             'to'         => $windowEnd->toDateTimeString(),
    //         ]);

    //         /*
    //         |------------------------------------------------------
    //         | 2. Insert analytics for THIS EXACT window
    //         |------------------------------------------------------
    //         | - LEFT JOIN ensures BLUE for missing devices
    //         | - clock_time is the branch identifier
    //         */
    //         DB::statement("
    //             INSERT INTO device_6h_analytics (
    //                 snapshot_time,
    //                 clock_time,
    //                 device,
    //                 total_records,
    //                 network_missing,
    //                 final_color,
    //                 decision_reason,
    //                 uv_on_count_6h,
    //                 pir_on_count_6h,
    //                 pir_off_count_3000,
    //                 temp_min,
    //                 temp_max,
    //                 temp_avg,
    //                 created_at,
    //                 updated_at
    //             )
    //             SELECT
    //                 NOW(),
    //                 '{$clockTime}',
    //                 m.DEVICE,
    //                 COUNT(s.device),
    //                 CASE WHEN COUNT(s.device) = 0 THEN 1 ELSE 0 END,
    //                 CASE WHEN COUNT(s.device) = 0 THEN 'BLUE' ELSE 'GREEN' END,
    //                 CASE WHEN COUNT(s.device) = 0 THEN 'No data in this 6h window' ELSE NULL END,
    //                 SUM(CASE WHEN s.uv = 'ON' THEN 1 ELSE 0 END),
    //                 SUM(CASE WHEN s.pir = 'ON' THEN 1 ELSE 0 END),
    //                 SUM(CASE WHEN s.rn <= 3000 AND s.pir = 'OFF' THEN 1 ELSE 0 END),
    //                 MIN(s.temp),
    //                 MAX(s.temp),
    //                 AVG(s.temp),
    //                 NOW(),
    //                 NOW()
    //             FROM DeviceInfo2 m
    //             LEFT JOIN device_logs_6h_staging s
    //                 ON s.device = m.DEVICE
    //                 AND s.time >= ?
    //                 AND s.time <  ?
    //             GROUP BY m.DEVICE
    //         ", [
    //             $windowStart->format('Y-m-d H:i:s'),
    //             $windowEnd->format('Y-m-d H:i:s'),
    //         ]);

    //         Log::info('NetworkIssue completed successfully', [
    //             'clock_time' => $clockTime
    //         ]);

    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('NetworkIssue failed', [
    //             'message' => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);
    //         return false;
    //     }
    // } working


    // private static function NetworkIssue()
    // {
    //     try {
    //         Log::info('NetworkIssue started');

    //         // Identify the 6h window
    //         $windowStart = DB::table('device_logs_6h_staging')
    //             ->selectRaw("
    //                 CASE
    //                     WHEN HOUR(MAX(time)) < 6  THEN DATE(MAX(time)) + INTERVAL 0 HOUR
    //                     WHEN HOUR(MAX(time)) < 12 THEN DATE(MAX(time)) + INTERVAL 6 HOUR
    //                     WHEN HOUR(MAX(time)) < 18 THEN DATE(MAX(time)) + INTERVAL 12 HOUR
    //                     ELSE DATE(MAX(time)) + INTERVAL 18 HOUR
    //                 END AS window_start
    //             ")
    //             ->value('window_start');

    //         if (!$windowStart) return false;

    //         $windowStart = Carbon::parse($windowStart);
    //         $windowEnd   = $windowStart->copy()->addHours(6);
    //         $clockTime   = $windowStart->format('Y-m-d H:i:s');

    //         // Check duplicates
    //         if (DB::table('device_6h_analytics')->where('clock_time', $clockTime)->exists()) {
    //             return false;
    //         }

    //         // 1. Standard Insert (Removed s.rn to fix Syntax Error)
    //         DB::statement("
    //             INSERT INTO device_6h_analytics (
    //                 snapshot_time, clock_time, device, total_records,
    //                 network_missing, final_color, decision_reason,
    //                 uv_on_count_6h, pir_on_count_6h,
    //                 temp_min, temp_max, temp_avg, created_at, updated_at
    //             )
    //             SELECT
    //                 NOW(), '{$clockTime}', m.DEVICE, COUNT(s.device),
    //                 CASE WHEN COUNT(s.device) = 0 THEN 1 ELSE 0 END,
    //                 CASE WHEN COUNT(s.device) = 0 THEN 'BLUE' ELSE 'GREEN' END,
    //                 CASE WHEN COUNT(s.device) = 0 THEN 'No data in this 6h window' ELSE NULL END,
    //                 SUM(CASE WHEN s.uv = 'ON' THEN 1 ELSE 0 END),
    //                 SUM(CASE WHEN s.pir = 'ON' THEN 1 ELSE 0 END),
    //                 MIN(s.temp), MAX(s.temp), AVG(s.temp), NOW(), NOW()
    //             FROM DeviceInfo2 m
    //             LEFT JOIN device_logs_6h_staging s ON s.device = m.DEVICE
    //                 AND s.time >= ? AND s.time < ?
    //             GROUP BY m.DEVICE
    //         ", [
    //             $windowStart->format('Y-m-d H:i:s'),
    //             $windowEnd->format('Y-m-d H:i:s'),
    //         ]);

    //         // 2. Now call the PIR function using the SAME window variables
    //         // This function will fill the pir_off_count_3000 column correctly.
    //         self::CalculatePirOff3000($clockTime, $windowStart, $windowEnd);

    //         Log::info('NetworkIssue completed successfully');
    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('NetworkIssue failed', ['message' => $e->getMessage()]);
    //         return false;
    //     }
    // }


    private static function NetworkIssue($requestedTime = null)
    {
        try {
            Log::info('NetworkIssue started');

            // 1. If we have a requested time from the $request, use it.
            // Otherwise, calculate the window from the staging data.
            if ($requestedTime) {
                $windowStart = Carbon::parse($requestedTime);
            } else {
                $windowStartStr = DB::table('device_logs_6h_staging')
                    ->selectRaw("
                        CASE
                            WHEN HOUR(MAX(time)) < 6  THEN DATE(MAX(time)) + INTERVAL 0 HOUR
                            WHEN HOUR(MAX(time)) < 12 THEN DATE(MAX(time)) + INTERVAL 6 HOUR
                            WHEN HOUR(MAX(time)) < 18 THEN DATE(MAX(time)) + INTERVAL 12 HOUR
                            ELSE DATE(MAX(time)) + INTERVAL 18 HOUR
                        END AS window_start
                    ")
                    ->value('window_start');

                if (!$windowStartStr) return false;
                $windowStart = Carbon::parse($windowStartStr);
            }

            $clockTime = $windowStart->format('Y-m-d H:i:s');
            $windowEnd = $windowStart->copy()->addHours(6);

            // 2. Check duplicates for this specific clock_time
            if (DB::table('device_6h_analytics')->where('clock_time', $clockTime)->exists()) {
                Log::info("Window $clockTime already exists. Returning time for update.");
                return $clockTime;
            }

            // 3. Insert new records for this 6h window
            DB::statement("
                INSERT INTO device_6h_analytics (
                    snapshot_time, clock_time, device, total_records,
                    network_missing, final_color, decision_reason,
                    uv_on_count_6h, pir_on_count_6h,
                    temp_min, temp_max, temp_avg, created_at, updated_at
                )
                SELECT
                    NOW(), '{$clockTime}', m.DEVICE, COUNT(s.device),
                    CASE WHEN COUNT(s.device) = 0 THEN 1 ELSE 0 END,
                    CASE WHEN COUNT(s.device) = 0 THEN 'BLUE' ELSE 'GREEN' END,
                    CASE WHEN COUNT(s.device) = 0 THEN 'No data in this 6h window' ELSE NULL END,
                    SUM(CASE WHEN s.uv = 'ON' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN s.pir = 'ON' THEN 1 ELSE 0 END),
                    MIN(s.temp), MAX(s.temp), AVG(s.temp), NOW(), NOW()
                FROM DeviceInfo2 m
                LEFT JOIN device_logs_6h_staging s ON s.device = m.DEVICE
                    AND s.time >= ? AND s.time < ?
                GROUP BY m.DEVICE
            ", [
                $windowStart->format('Y-m-d H:i:s'),
                $windowEnd->format('Y-m-d H:i:s'),
            ]);

            // 4. Run the special PIR count for the same window
            self::CalculatePirOff3000($clockTime, $windowStart, $windowEnd);

            Log::info('NetworkIssue completed successfully', ['clock_time' => $clockTime]);

            // Return the clockTime so CalculatErrorState can pass it to TOF, UV, etc.
            return $clockTime;

        } catch (\Throwable $e) {
            Log::error('NetworkIssue failed', ['message' => $e->getMessage()]);
            return false;
        }
    }


    private static function CalculatePirOff3000($clockTime, $windowStart, $windowEnd)
    {
        try {
            Log::info('CalculatePirOff3000: Starting', ['clock_time' => $clockTime]);

            // 1. Get devices using clock_time
            $devices = DB::table('device_6h_analytics')
                ->where('clock_time', $clockTime)
                ->where('network_missing', 0)
                ->pluck('device');

            foreach ($devices as $deviceName) {
                // 2. Fetch the actual logs (max 3000)
                $logs = DB::table('device_logs_6h_staging')
                    ->where('device', $deviceName)
                    ->where('time', '>=', $windowStart)
                    ->where('time', '<', $windowEnd)
                    ->orderBy('time', 'desc')
                    ->limit(3000)
                    ->get();

                $actualRowCount = $logs->count();

                // 3. Logic: ONLY flag pir_issue if we have exactly 3000 rows to analyze
                if ($actualRowCount === 3000) {
                    $pirOffCount = $logs->where('pir', 'OFF')->count();

                    DB::table('device_6h_analytics')
                        ->where('clock_time', $clockTime)
                        ->where('device', $deviceName)
                        ->update([
                            'pir_off_count_3000' => $pirOffCount,
                            'pir_issue' => DB::raw("CASE
                                WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0
                                AND {$pirOffCount} < 10 THEN 1
                                ELSE 0 END")
                        ]);
                } else {
                    // Not enough data (less than 3000 rows), so it cannot be a PIR issue yet
                    DB::table('device_6h_analytics')
                        ->where('clock_time', $clockTime)
                        ->where('device', $deviceName)
                        ->update([
                            'pir_off_count_3000' => $actualRowCount,
                            'pir_issue' => 0
                        ]);
                }
            }

            Log::info('CalculatePirOff3000: Completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('CalculatePirOff3000 failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // Check TOF issue
    // private static function TOFIssue()
    // {
    //     DB::statement("
    //         UPDATE device_6h_analytics a

    //         /* latest snapshot time */
    //         JOIN (
    //             SELECT MAX(snapshot_time) AS max_snapshot_time
    //             FROM device_6h_analytics
    //         ) mx ON a.snapshot_time = mx.max_snapshot_time

    //         /* latest mm per device */
    //         JOIN (
    //             SELECT s1.device, s1.mm AS latest_mm
    //             FROM device_logs_6h_staging s1
    //             JOIN (
    //                 SELECT device, MAX(time) AS max_time
    //                 FROM device_logs_6h_staging
    //                 GROUP BY device
    //             ) s2
    //                 ON s2.device = s1.device
    //             AND s2.max_time = s1.time
    //         ) lm ON lm.device = a.device

    //         /* TOF fault count per device */
    //         JOIN (
    //             SELECT device, SUM(mm BETWEEN 1 AND 40) AS tof_fault_count
    //             FROM device_logs_6h_staging
    //             GROUP BY device
    //         ) tc ON tc.device = a.device

    //         SET
    //             a.tof_fault_count = tc.tof_fault_count,

    //             a.tof_issue = CASE
    //                 WHEN lm.latest_mm BETWEEN 1 AND 40 THEN 1
    //                 ELSE 0
    //             END,

    //             a.decision_reason = CASE
    //                 WHEN a.network_missing = 1 THEN a.decision_reason
    //                 WHEN lm.latest_mm BETWEEN 1 AND 30
    //                     THEN 'TOF sensor fault (latest mm in 1–40 range)'
    //                 ELSE a.decision_reason
    //             END,

    //             a.updated_at = NOW()
    //     ");
    // }

    private static function TOFIssue($clockTime)
    {
        try {
            Log::info('TOFIssue started', ['clock_time' => $clockTime]);

            DB::statement("
                UPDATE device_6h_analytics a

                /* 1. Join with latest mm per device from staging */
                JOIN (
                    SELECT s1.device, s1.mm AS latest_mm
                    FROM device_logs_6h_staging s1
                    JOIN (
                        SELECT device, MAX(time) AS max_time
                        FROM device_logs_6h_staging
                        GROUP BY device
                    ) s2 ON s2.device = s1.device AND s2.max_time = s1.time
                ) lm ON lm.device = a.device

                /* 2. Join with TOF fault count per device from staging */
                JOIN (
                    SELECT device, SUM(mm BETWEEN 1 AND 40) AS tof_fault_count
                    FROM device_logs_6h_staging
                    GROUP BY device
                ) tc ON tc.device = a.device

                SET
                    a.tof_fault_count = tc.tof_fault_count,

                    a.tof_issue = CASE
                        WHEN lm.latest_mm BETWEEN 1 AND 40 THEN 1
                        ELSE 0
                    END,

                    a.decision_reason = CASE
                        WHEN a.network_missing = 1 THEN a.decision_reason
                        WHEN lm.latest_mm BETWEEN 1 AND 40
                            THEN 'TOF sensor fault (latest mm in 1–40 range)'
                        ELSE a.decision_reason
                    END,

                    a.updated_at = NOW()

                /* ⭐ THE FIX: Only update the specific 6h window rows */
                WHERE a.clock_time = ?
            ", [$clockTime]);

            Log::info('TOFIssue completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('TOFIssue failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // private static function TempIssue()
    // {
    //     DB::statement("
    //         UPDATE device_6h_analytics a
    //         JOIN (
    //             SELECT s.device, s.temp
    //             FROM device_logs_6h_staging s
    //             INNER JOIN (
    //                 SELECT device, MAX(time) AS last_time
    //                 FROM device_logs_6h_staging
    //                 GROUP BY device
    //             ) x ON x.device = s.device AND x.last_time = s.time
    //         ) last_row ON last_row.device = a.device
    //         SET
    //             a.temp_issue = CASE
    //                 WHEN last_row.temp < 720 OR last_row.temp > 3000 THEN 1
    //                 ELSE 0
    //             END,
    //             a.decision_reason = CASE
    //                 -- Parentheses are vital here to keep your AND logic intact
    //                 WHEN (last_row.temp < 720 OR last_row.temp > 3000) AND a.network_missing = 0
    //                     THEN 'Temperature out of range (<720 or >3000)'
    //                 ELSE a.decision_reason
    //             END
    //     ");
    // }


    private static function TempIssue($clockTime)
    {
        try {
            Log::info('TempIssue started', ['clock_time' => $clockTime]);

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
                        WHEN last_row.temp < 720 OR last_row.temp > 3000 THEN 1
                        ELSE 0
                    END,
                    a.decision_reason = CASE
                        WHEN (last_row.temp < 720 OR last_row.temp > 3000) AND a.network_missing = 0
                            THEN 'Temperature out of range (<720 or >3000)'
                        ELSE a.decision_reason
                    END,
                    a.updated_at = NOW()
                /* ⭐ THE FIX: Anchor to the specific window */
                WHERE a.clock_time = ?
            ", [$clockTime]);

            Log::info('TempIssue completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('TempIssue failed', ['message' => $e->getMessage()]);
            return false;
        }
    }


    // private static function UVCheckForLast30(){
    //     DB::statement("
    //         UPDATE device_6h_analytics a
    //         JOIN (
    //             SELECT DISTINCT device
    //             FROM (
    //                 SELECT
    //                     device,
    //                     uv,
    //                     @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
    //                     @prev_device := device
    //                 FROM device_logs_6h_staging
    //                 CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
    //                 ORDER BY device, time DESC
    //             ) ranked
    //             WHERE rn <= 30
    //             AND TRIM(UPPER(uv)) = 'ON'
    //         ) u ON TRIM(u.device) = TRIM(a.device)
    //         SET
    //             a.uv_pass_30 = 1,
    //             a.decision_reason = 'UV ON detected in last 30 records'
    //     ");
    // }


    private static function UVCheckForLast30($clockTime)
    {
        try {
            Log::info('UVCheckForLast30 started', ['clock_time' => $clockTime]);

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
                    a.decision_reason = 'UV ON detected in last 30 records',
                    a.updated_at = NOW()
                /* ⭐ THE FIX: Only update the current window */
                WHERE a.clock_time = ?
            ", [$clockTime]);

            Log::info('UVCheckForLast30 completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('UVCheckForLast30 failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // private static function UVCheckForLast200()
    // {
    //     DB::statement("UPDATE device_6h_analytics a
    //         SET
    //             a.uv_pass_200 = 1,
    //             a.decision_reason = 'UV ON detected in last 200 records'
    //         WHERE
    //             (a.final_color IS NULL OR a.final_color = 'GREEN')
    //             AND EXISTS (
    //                 SELECT 1
    //                 FROM (
    //                     SELECT
    //                         device,
    //                         uv,
    //                         @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
    //                         @prev_device := device
    //                     FROM device_logs_6h_staging
    //                     CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
    //                     ORDER BY device, time DESC
    //                 ) ranked
    //                 WHERE
    //                     ranked.device = a.device
    //                     AND ranked.rn <= 200
    //                     AND TRIM(UPPER(ranked.uv)) = 'ON'
    //             );"
    //         );
    // }

    private static function UVCheckForLast200($clockTime)
    {
        try {
            Log::info('UVCheckForLast200 started', ['clock_time' => $clockTime]);

            DB::statement("
                UPDATE device_6h_analytics a
                SET
                    a.uv_pass_200 = 1,
                    a.decision_reason = 'UV ON detected in last 200 records',
                    a.updated_at = NOW()
                WHERE
                    /* ⭐ THE FIX: Anchor to the specific window */
                    a.clock_time = ?

                    AND (a.final_color IS NULL OR a.final_color = 'GREEN')
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
                    )
            ", [$clockTime]);

            Log::info('UVCheckForLast200 completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('UVCheckForLast200 failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // private static function UVCheckForLast1000()
    // {
    //     DB::statement("
    //         UPDATE device_6h_analytics a
    //         SET
    //             a.uv_pass_1000 = 1,
    //             a.decision_reason = 'UV ON detected in last 3000 records'
    //         WHERE
    //             (a.final_color IS NULL OR a.final_color = 'GREEN')
    //             AND EXISTS (
    //                 SELECT 1
    //                 FROM (
    //                     SELECT
    //                         device,
    //                         uv,
    //                         @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
    //                         @prev_device := device
    //                     FROM device_logs_6h_staging
    //                     CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
    //                     ORDER BY device, time DESC
    //                 ) ranked
    //                 WHERE
    //                     ranked.device = a.device
    //                     AND ranked.rn <= 3000
    //                     AND TRIM(UPPER(ranked.uv)) = 'ON'
    //             )
    //     ");
    // }


    private static function UVCheckForLast1000($clockTime)
    {
        try {
            Log::info('UVCheckForLast1000 started', ['clock_time' => $clockTime]);

            DB::statement("
                UPDATE device_6h_analytics a
                SET
                    a.uv_pass_1000 = 1,
                    a.decision_reason = 'UV ON detected in last 3000 records',
                    a.updated_at = NOW()
                WHERE
                    /* ⭐ THE FIX: Anchor to the specific window */
                    a.clock_time = ?

                    AND (a.final_color IS NULL OR a.final_color = 'GREEN')
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
                            AND ranked.rn <= 3000
                            AND TRIM(UPPER(ranked.uv)) = 'ON'
                    )
            ", [$clockTime]);

            Log::info('UVCheckForLast1000 completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('UVCheckForLast1000 failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // private static function UVFailLast1000()
    // {
    //     DB::statement("
    //         UPDATE device_6h_analytics a
    //         SET
    //             a.uv_pass_1000 = 0,
    //             a.decision_reason = 'No UV ON detected in last 1000 records – manual attention required'
    //         WHERE
    //             (a.final_color IS NULL OR a.final_color = 'GREEN')
    //             AND NOT EXISTS (
    //                 SELECT 1
    //                 FROM (
    //                     SELECT
    //                         device,
    //                         uv,
    //                         @rn := IF(@prev_device = device, @rn + 1, 1) AS rn,
    //                         @prev_device := device
    //                     FROM device_logs_6h_staging
    //                     CROSS JOIN (SELECT @rn := 0, @prev_device := '') vars
    //                     ORDER BY device, time DESC
    //                 ) ranked
    //                 WHERE
    //                     ranked.device = a.device
    //                     AND ranked.rn <= 3000
    //                     AND TRIM(UPPER(ranked.uv)) = 'ON'
    //             )
    //     ");
    // }


    private static function UVFailLast1000($clockTime)
    {
        try {
            Log::info('UVFailLast1000 started', ['clock_time' => $clockTime]);

            DB::statement("
                UPDATE device_6h_analytics a
                SET
                    a.uv_pass_1000 = 0,
                    a.decision_reason = 'No UV ON detected in last 3000 records – manual attention required',
                    a.updated_at = NOW()
                WHERE
                    /* ⭐ THE FIX: Anchor to the specific window */
                    a.clock_time = ?

                    AND (a.final_color IS NULL OR a.final_color = 'GREEN')
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
                            /* Note: Matches your previous logic of 3000 */
                            AND ranked.rn <= 3000
                            AND TRIM(UPPER(ranked.uv)) = 'ON'
                    )
            ", [$clockTime]);

            Log::info('UVFailLast1000 completed successfully');
            return true;

        } catch (\Throwable $e) {
            Log::error('UVFailLast1000 failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // private static function CheckPirStatus()
    // {
    //     try {
    //         Log::info('CheckPirStatus: Evaluating PIR issues for UV-dead devices');

    //         /*
    //         | Logic:
    //         | 1. (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0  => UV is completely dead
    //         | 2. pir_off_count_3000 < 10                       => PIR is not sending 'OFF' signals
    //         | 3. network_missing = 0                           => Only check devices with data
    //         */

    //         DB::statement("
    //             UPDATE device_6h_analytics
    //             SET
    //                 pir_issue = 1,
    //                 decision_reason = 'PIR Issue: UV dead & PIR inactive (<10 OFF)'
    //             WHERE
    //                 (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0
    //                 AND pir_off_count_3000 < 10
    //         ");

    //         Log::info('CheckPirStatus: Completed successfully');
    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('CheckPirStatus failed', ['message' => $e->getMessage()]);
    //         return false;
    //     }
    // }

    // private static function CheckPirStatus($clockTime)
    // {
    //     try {
    //         Log::info('CheckPirStatus: Evaluating PIR issues for UV-dead devices', ['clock_time' => $clockTime]);

    //         /*
    //         | Logic:
    //         | 1. (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0  => UV is completely dead
    //         | 2. pir_off_count_3000 < 10                       => PIR is not sending 'OFF' signals
    //         | 3. clock_time = ?                                => Only update the specific 6h slot
    //         */

    //         DB::statement("
    //             UPDATE device_6h_analytics
    //             SET
    //                 pir_issue = 1,
    //                 decision_reason = 'PIR Issue: UV dead & PIR inactive (<10 OFF)',
    //                 updated_at = NOW()
    //             WHERE
    //                 /* ⭐ THE FIX: Anchor to the specific window */
    //                 clock_time = ?
    //                 AND (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0
    //                 AND pir_off_count_3000 < 10
    //         ", [$clockTime]);

    //         Log::info('CheckPirStatus: Completed successfully');
    //         return true;

    //     } catch (\Throwable $e) {
    //         Log::error('CheckPirStatus failed', ['message' => $e->getMessage()]);
    //         return false;
    //     }
    // }

// public static function CheckDeviceDiagnostics($request)
// {
//     $now = $request->time ? Carbon::parse($request->time) : Carbon::now();
//     $currentDateStr = $now->copy()->startOfDay()->addHours(floor($now->hour / 6) * 6)->toDateTimeString();

//     $deviceMeta = DB::table('DeviceInfo2')->get();
//     $statusMap = [];
//     $pairedTypes = ['U', 'T', 'BU', 'BT'];

//     // --- STEP 1: 1-by-1 HISTORY TEST ---
//     foreach ($deviceMeta as $m) {
//         $dId = (string)$m->DEVICE;

//         $history = DB::table('device_6h_analytics')
//             ->where('device', $dId)
//             ->where('clock_time', '<=', $currentDateStr)
//             ->orderBy('clock_time', 'desc')
//             ->take(12)
//             ->get();

//         $realOffline = $history->where('total_records', 0)->count();
//         $ghosts = max(0, 12 - $history->count());
//         $isDead = ($realOffline + $ghosts) >= 12;

//         // Get the last real hardware issue (Inheritance)
//         $lastOnlineRow = $history->where('total_records', '>', 0)->first();
//         $oldIssueColor = 'GREEN';
//         if ($lastOnlineRow) {
//             $rawColor = $lastOnlineRow->final_color;
//             $oldIssueColor = !in_array($rawColor, ['BLUE', 'CYAN']) ? $rawColor : 'GREEN';
//         }

//         $statusMap[$dId] = [
//             'is_dead' => $isDead,
//             'old_issue' => $oldIssueColor,
//             'type' => trim($m->TYPE),
//             'car' => $m->CAR
//         ];
//     }

//     // --- STEP 2: APPLY FINAL COLORS ---
//     foreach ($deviceMeta as $meta) {
//         $deviceId = (string)$meta->DEVICE;
//         $current = DB::table('device_6h_analytics')
//             ->where('device', $deviceId)
//             ->where('clock_time', $currentDateStr)
//             ->first();

//         if (!$current) continue;

//         $my = $statusMap[$deviceId];
//         $isPairedType = in_array($my['type'], $pairedTypes);
//         $updatePayload = [];

//         if ($current->total_records > 0) {
//             // --- ONLINE LOGIC: DO NOT AUTO-GREEN ---
//             // Check current hardware issues first
//             $currentColor = 'GREEN';
//             $reason = "Device online and healthy";

//             if ($current->temp_issue == 1) { $currentColor = 'ORANGE'; $reason = "Temperature issue"; }
//             elseif ($current->tof_issue == 1) { $currentColor = 'YELLOW'; $reason = "TOF sensor issue"; }
//             elseif ($current->uv_pass_30 == 0) { $currentColor = 'MAGENTA'; $reason = "UV Issue detected"; }
//             elseif($current->pir_issue == 1) { $currentColor = 'RED'; $reason = "PIR sensor issue"; }

//             $updatePayload['final_color'] = $currentColor;
//             $updatePayload['decision_reason'] = $reason;
//             $updatePayload['uv_persistent_fail'] = 0;
//         }
//         else {
//             // --- OFFLINE LOGIC (Paired vs Single) ---
//             $finalColor = 'GREEN';
//             if ($isPairedType) {
//                 $partner = $deviceMeta->where('CAR', $meta->CAR)->where('DEVICE', '!=', $deviceId)->first();
//                 $pStatus = $statusMap[(string)data_get($partner, 'DEVICE')] ?? ['is_dead' => true];

//                 if ($my['is_dead'] && $pStatus['is_dead']) {
//                     $finalColor = 'CYAN';
//                 } elseif ($my['is_dead'] && !$pStatus['is_dead']) {
//                     $finalColor = 'BLUE';
//                 } else {
//                     $finalColor = $my['old_issue'];
//                 }
//             } else {
//                 if ($my['is_dead']) {
//                     $finalColor = 'CYAN';
//                 } else {
//                     $finalColor = $my['old_issue'];
//                 }
//             }
//             $updatePayload['final_color'] = $finalColor;
//             $updatePayload['uv_persistent_fail'] = ($finalColor == 'CYAN' ? 1 : 0);
//             $updatePayload['decision_reason'] = "12-slot inheritance logic applied";
//         }

//         DB::table('device_6h_analytics')->where('id', $current->id)->update($updatePayload);
//     }
//     return true;
// } / 2 working code

    public static function CheckDeviceDiagnostics($request)
    {
        $now = $request->time ? Carbon::parse($request->time) : Carbon::now();
        // Round to the nearest 6h window start
        $currentDateStr = $now->copy()->startOfDay()->addHours(floor($now->hour / 6) * 6)->toDateTimeString();

        $deviceMeta = DB::table('DeviceInfo2')->get();
        $statusMap = [];
        $pairedTypes = ['U', 'T', 'BU', 'BT'];

        // --- STEP 1: PRE-CALCULATE HISTORY FOR OFFLINE DEVICES ---
        foreach ($deviceMeta as $m) {
            $dId = (string)$m->DEVICE;

            // Fetch last 12 slots (72 hours)
            $history = DB::table('device_6h_analytics')
                ->where('device', $dId)
                ->where('clock_time', '<=', $currentDateStr)
                ->orderBy('clock_time', 'desc')
                ->take(12)
                ->get();

            $realOffline = $history->where('total_records', 0)->count();
            $ghosts = max(0, 12 - $history->count());
            $isDead = ($realOffline + $ghosts) >= 12;

            // FIX: Find the VERY LAST slot where the device actually sent data
            $lastActiveSlot = $history->first(function($row) {
                return $row->total_records > 0;
            });

            $statusMap[$dId] = [
                'is_dead' => $isDead,
                // Copy whatever the last color was (Green, Magenta, etc.)
                'old_status' => $lastActiveSlot ? $lastActiveSlot->final_color : 'GREEN',
                // Track the time for the decision reason
                'source_time' => $lastActiveSlot ? $lastActiveSlot->clock_time : 'N/A',
                'type' => trim($m->TYPE),
                'car' => $m->CAR
            ];
        }

        // --- STEP 2: APPLY OVERRIDE ONLY TO OFFLINE RECORDS ---
        foreach ($deviceMeta as $meta) {
            $deviceId = (string)$meta->DEVICE;
            $current = DB::table('device_6h_analytics')
                ->where('device', $deviceId)
                ->where('clock_time', $currentDateStr)
                ->first();

            // Skip if current window doesn't exist yet
            if (!$current) continue;

            // RULE: If device is online in this window, don't override logic
            if ($current->total_records > 0) {
                continue;
            }

            $my = $statusMap[$deviceId];
            $isPairedType = in_array($my['type'], $pairedTypes);
            $finalColor = 'GREEN';
            $reason = "";

            if ($isPairedType) {
                // --- PAIR LOGIC ---
                $partner = $deviceMeta->where('CAR', $meta->CAR)->where('DEVICE', '!=', $deviceId)->first();
                $pStatus = $statusMap[(string)data_get($partner, 'DEVICE')] ?? ['is_dead' => true];

                if ($my['is_dead'] && $pStatus['is_dead']) {
                    $finalColor = 'CYAN';
                    $reason = "Both Pair Devices Dead (>72h)";
                } elseif ($my['is_dead'] && !$pStatus['is_dead']) {
                    $finalColor = 'BLUE';
                    $reason = "Device Dead, Partner Online";
                } else {
                    // Not dead, just temporarily offline. Copy last active state.
                    $finalColor = $my['old_status'];
                    $reason = "Inherited: {$finalColor} from active slot at {$my['source_time']}";
                }
            } else {
                // --- SINGLE LOGIC ---
                if ($my['is_dead']) {
                    $finalColor = 'CYAN';
                    $reason = "Single Device Dead (>72h)";
                } else {
                    $finalColor = $my['old_status'];
                    $reason = "Inherited: {$finalColor} from active slot at {$my['source_time']}";
                }
            }

            DB::table('device_6h_analytics')->where('id', $current->id)->update([
                'final_color' => $finalColor,
                // Keep persistent fail active only if it was already marked as an issue
                'uv_persistent_fail' => in_array($finalColor, ['MAGENTA', 'RED', 'ORANGE']) ? 1 : 0,
                'decision_reason' => $reason . " (Offline Logic)",
                'updated_at' => now()
            ]);
        }

        return true;
    }


    // private static function ResolveFinalState($currentDate)
    // {
    //     // Ensure the date is in the correct string format for the SQL query
    //     $snapshotTime = $currentDate;

    //     DB::statement("
    //         UPDATE device_6h_analytics
    //         SET
    //             final_color = CASE
    //                 /* 🔵 BLUE: Standard network missing for this specific slot */
    //                 WHEN network_missing = 1 THEN 'BLUE'

    //                 /* 🟡 YELLOW: TOF issue */
    //                 WHEN tof_issue = 1 THEN 'YELLOW'

    //                 /* 🟠 ORANGE: Temp issue */
    //                 WHEN temp_issue = 1 THEN 'ORANGE'

    //                 /* 🟢 GREEN: Successful UV ON */
    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0 THEN 'GREEN'

    //                 /* 🩷 MAGENTA: UV is simply OFF but network is fine */
    //                 ELSE 'MAGENTA'
    //             END,

    //             decision_reason = CASE
    //                 WHEN network_missing = 1 THEN 'No data in this 6h window'
    //                 WHEN tof_issue = 1 THEN 'TOF issue detected'
    //                 WHEN temp_issue = 1 THEN 'Temperature below threshold'
    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) > 0 THEN 'UV ON detected in recent history'
    //                 ELSE 'UV OFF in 30/200/1000 records'
    //             END
    //         /* THE FIX: This WHERE clause ensures that only the data for
    //         the current date/time you are processing gets updated.
    //         */
    //         WHERE snapshot_time = '{$snapshotTime}'
    //     ");
    // } working


    // private static function ResolveFinalState($currentDate)
    // {
    //     $snapshotTime = $currentDate;

    //     DB::statement("
    //         UPDATE device_6h_analytics
    //         SET
    //             final_color = CASE
    //                 /* 1. Network Check (Highest Priority) */
    //                 WHEN network_missing = 1 THEN 'BLUE'

    //                 /* 2. TOF Hardware Check */
    //                 WHEN tof_issue = 1 THEN 'YELLOW'

    //                 /* 3. Temperature Hardware Check */
    //                 WHEN temp_issue = 1 THEN 'ORANGE'

    //                 /* 4. UV History Check */
    //                 /* If UV is dead, we check if it's a PIR failure or just UV OFF */
    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0 THEN
    //                     CASE
    //                         WHEN pir_issue = 1 THEN 'RED'
    //                         ELSE 'MAGENTA'
    //                     END

    //                 /* 5. Success Check (Everything else is OK) */
    //                 ELSE 'GREEN'
    //             END,

    //             decision_reason = CASE
    //                 WHEN network_missing = 1 THEN 'No data in this 6h window'
    //                 WHEN tof_issue = 1      THEN 'TOF issue detected'
    //                 WHEN temp_issue = 1     THEN 'Temperature below threshold'

    //                 /* UV Dead Branch */
    //                 WHEN (uv_pass_30 + uv_pass_200 + uv_pass_1000) = 0 THEN
    //                     CASE
    //                         WHEN pir_issue = 1 THEN 'RED: PIR Hardware Issue (UV dead & PIR inactive)'
    //                         ELSE 'UV OFF in 30/200/1000 records'
    //                     END

    //                 ELSE 'All systems normal - UV active'
    //             END

    //         WHERE snapshot_time = '{$snapshotTime}'
    //     ");
    // }


    private static function ResolveFinalState($currentDate)
    {
        $clockTime = $currentDate; // The 18:00:00 time from your logs

        // --- LOGGING STEP ---
        // Fixed: changed snapshot_time to clock_time
        // $debugRows = DB::table('device_6h_analytics')
        //     ->where('clock_time', $clockTime)
        //     ->get(['device', 'uv_pass_30', 'uv_pass_200', 'uv_pass_1000', 'network_missing']);

        // Log::info("ResolveFinalState checking clock_time: $clockTime. Found rows: " . $debugRows->count());

        // foreach ($debugRows as $row) {
        //     Log::info("DEBUG Device {$row->device}: 30={$row->uv_pass_30}, 200={$row->uv_pass_200}, 1000={$row->uv_pass_1000}, NetMiss={$row->network_missing}");
        // }

        // --- UPDATED SQL ---
        DB::statement("
            UPDATE device_6h_analytics
            SET
                final_color = CASE
                    WHEN network_missing = 1 THEN 'BLUE'
                    WHEN tof_issue = 1 THEN 'YELLOW'
                    WHEN temp_issue = 1 THEN 'ORANGE'

                    /* Strict UV Check */
                    WHEN (IFNULL(uv_pass_30, 0) = 0 AND
                        IFNULL(uv_pass_200, 0) = 0 AND
                        IFNULL(uv_pass_1000, 0) = 0) THEN
                        CASE
                            WHEN pir_issue = 1 THEN 'RED'
                            ELSE 'MAGENTA'
                        END

                    ELSE 'GREEN'
                END,
                decision_reason = CASE
                    WHEN network_missing = 1 THEN 'No data in window'
                    WHEN (IFNULL(uv_pass_30, 0) = 0 AND IFNULL(uv_pass_200, 0) = 0 AND IFNULL(uv_pass_1000, 0) = 0)
                        THEN 'SQL FORCED MAGENTA: All UV Pass columns are 0'
                    ELSE 'All systems normal - UV active'
                END
            /* Fixed: changed snapshot_time to clock_time */
            WHERE clock_time = '{$clockTime}'
        ");
    }

    public static function deleteOldData()
    {
        $cutoff = Carbon::now('Asia/Seoul')->subDays(15)->format('Y-m-d H:i:s');

        // 1. Delete from Local Database
        $localDeleted = DB::table('TestMuguhwa')->where('TIME', '<', $cutoff)->delete();
        Log::info("Local DB: Deleted {$localDeleted} rows.");

        // 2. Delete from External Databases
        $externalConnections = ['external_db', 'external_db2'];

        foreach ($externalConnections as $connectionName) {
            try {
                $count = DB::connection($connectionName)
                    ->table('TestMuguhwa')
                    ->where('TIME', '<', $cutoff)
                    ->delete();

                Log::info("External DB ({$connectionName}): Deleted {$count} rows.");
            } catch (\Exception $e) {
                Log::error("Failed to delete from {$connectionName}: " . $e->getMessage());
            }
        }
    }

}

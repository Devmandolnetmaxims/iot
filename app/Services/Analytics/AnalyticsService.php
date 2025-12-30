<?php

namespace App\Services\Analytics;

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

            // AnalyticsService::CalculatErrorState();
            return true;

        } catch (\Throwable $e) {
            Log::error('Load6hrawdata failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    // Calculate error state
    public static function CalculatErrorState($request = null)
    {
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
    }

    // Check network issue
    private static function NetworkIssue() {
        try{
            Log::info('NetworkIssue started');
            // truncate the data first
            DB::statement("
                INSERT INTO device_6h_analytics (
                    snapshot_time, device, total_records, network_missing,
                    final_color, decision_reason, uv_on_count_6h, pir_on_count_6h, temp_min, temp_max, temp_avg,created_at, updated_at
                )
                SELECT
                    NOW() as snapshot_time,
                    m.DEVICE as device,
                    COUNT(s.device) as total_records,
                    CASE WHEN COUNT(s.device) = 0 THEN 1 ELSE 0 END as network_missing,
                    CASE WHEN COUNT(s.device) = 0 THEN 'BLUE' ELSE 'GREEN' END as final_color,
                    CASE WHEN COUNT(s.device) = 0 THEN 'No data in current 6h window' ELSE NULL END as decision_reason,
                    SUM(CASE WHEN s.uv = 'ON' THEN 1 ELSE 0 END) AS uv_on_count_6h,
                    SUM(CASE WHEN s.pir = 'ON' THEN 1 ELSE 0 END) AS pir_on_count_6h,
                    MIN(s.temp) AS temp_min,
                    MAX(s.temp) AS temp_max,
                    AVG(s.temp) AS temp_avg,
                    NOW(), NOW()
                FROM deviceinfo2 m
                LEFT JOIN device_logs_6h_staging s
                    ON s.device = m.DEVICE
                GROUP BY m.DEVICE
            ");
            return true;
        } catch (\Throwable $e) {
            Log::error('Calculate6hAnalytics failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    // Check TOF issue
    private static function TOFIssue() {
        DB::statement("
            UPDATE device_6h_analytics a
            JOIN (
                SELECT
                    s.device,

                    -- latest mm value
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(s.mm ORDER BY s.time DESC),
                        ',', 1
                    ) AS latest_mm,

                    -- count of TOF fault rows in 6h (diagnostics)
                    SUM(s.mm BETWEEN 1 AND 30) AS tof_fault_count

                FROM device_logs_6h_staging s
                GROUP BY s.device
            ) t ON t.device = a.device

            SET
                a.tof_fault_count = t.tof_fault_count,

                a.tof_issue = CASE
                    WHEN t.latest_mm BETWEEN 1 AND 30 THEN 1
                    ELSE 0
                END,

                a.final_color = CASE
                    WHEN a.network_missing = 1 THEN a.final_color
                    WHEN t.latest_mm BETWEEN 1 AND 30 THEN 'YELLOW'
                    ELSE a.final_color
                END,

                a.decision_reason = CASE
                    WHEN a.network_missing = 1 THEN a.decision_reason
                    WHEN t.latest_mm BETWEEN 1 AND 30
                        THEN 'TOF sensor fault (latest mm in 1–30 range)'
                    ELSE a.decision_reason
                END,

                a.updated_at = NOW()

            WHERE a.snapshot_time = (
                SELECT MAX(snapshot_time) FROM device_6h_analytics
            )
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
                a.final_color = CASE
                    WHEN last_row.temp < 720 AND a.network_missing = 0 THEN 'ORANGE'
                    ELSE a.final_color
                END,
                a.decision_reason = CASE
                    WHEN last_row.temp < 720 AND a.network_missing = 0
                        THEN 'Temperature below threshold (<720)'
                    ELSE a.decision_reason
                END
        ");
    }

    //
    private static function UVCheckForLast30()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            JOIN (
                SELECT DISTINCT device
                FROM (
                    SELECT
                        device,
                        uv,
                        ROW_NUMBER() OVER (
                            PARTITION BY device
                            ORDER BY time DESC
                        ) rn
                    FROM device_logs_6h_staging
                ) x
                WHERE rn <= 30
                AND TRIM(UPPER(uv)) = 'ON'
            ) u ON TRIM(u.device) = TRIM(a.device)
            SET
                a.uv_pass_30 = 1,
                a.final_color = 'GREEN',
                a.decision_reason = 'UV ON detected in last 30 records';
        ");
    }

    private static function UVCheckForLast200()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            SET
                a.uv_pass_200 = 1,
                a.final_color = 'GREEN',
                a.decision_reason = 'UV ON detected in last 200 records'
            WHERE (a.final_color IS NULL OR a.final_color = 'GREEN')
            AND EXISTS (
                SELECT 1
                FROM (
                    SELECT device, uv,
                        ROW_NUMBER() OVER (PARTITION BY device ORDER BY time DESC) rn
                    FROM device_logs_6h_staging
                ) t
                WHERE t.device = a.device
                AND t.rn <= 200
                AND t.uv = 'ON'
            )
        ");
    }

    private static function UVCheckForLast1000()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            SET
                a.uv_pass_1000 = 1,
                a.final_color = 'GREEN',
                a.decision_reason = 'UV ON detected in last 1000 records'
            WHERE (a.final_color IS NULL OR a.final_color = 'GREEN')
            AND EXISTS (
                SELECT 1
                FROM (
                    SELECT device, uv,
                        ROW_NUMBER() OVER (PARTITION BY device ORDER BY time DESC) rn
                    FROM device_logs_6h_staging
                ) t
                WHERE t.device = a.device
                AND t.rn <= 1000
                AND t.uv = 'ON'
            )
        ");
    }

    private static function UVFailLast1000()
    {
        DB::statement("
            UPDATE device_6h_analytics a
            SET
                a.uv_pass_1000 = 0,
                a.final_color = 'MAGENTA',
                a.decision_reason = 'No UV ON detected in last 1000 records – manual attention required'
            WHERE (a.final_color IS NULL OR a.final_color = 'GREEN')
            AND NOT EXISTS (
                SELECT 1
                FROM (
                    SELECT device, uv,
                        ROW_NUMBER() OVER (PARTITION BY device ORDER BY time DESC) rn
                    FROM device_logs_6h_staging
                ) t
                WHERE t.device = a.device
                AND t.rn <= 1000
                AND t.uv = 'ON'
            )
        ");
    }

}

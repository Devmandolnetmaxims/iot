<?php

namespace App\Http\Repository\Analytics;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsRepository {
    public static function getDeviceColors($request)
    {
        // Validation
        $validator = Validator::make($request->query(), [
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $dateTime = $request->query('date') . ' ' . $request->query('time');
        $dateTime = Carbon::parse($dateTime)->subHours(6);

        // Check if data exists
        $exists = DB::table('device_6h_analytics as a')
            ->where('a.clock_time', $dateTime)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => true,
                'data'    => [],
                'message' => 'No data for this time window'
            ], 200);
        }

        // JOIN with DeviceInfo2
        $data = DB::table('device_6h_analytics as a')
            ->join('DeviceInfo2 as d', 'd.DEVICE', '=', 'a.device')
            ->where('a.clock_time', $dateTime)
            ->select(
                'a.device',
                'a.final_color',
                'a.snapshot_time',
                'a.clock_time',
                'd.CAR',
                'd.TYPE',
                'd.INSTALL'
            )
            ->orderBy('a.device')
            ->get();

        // Color Details
        $counts = $data->countBy('final_color');
        $logDetails = collect([
            ['color' => 'GREEN',   'description' => '정상 (UV ON)'],
            ['color' => 'BLUE',    'description' => '한쪽 WiFi'],
            ['color' => 'CYAN',    'description' => 'Wifi 없음'],
            ['color' => 'ORANGE',  'description' => '온도 이상'],
            ['color' => 'YELLOW',  'description' => 'TOF / 막힘'],
            ['color' => 'MAGENTA', 'description' => '거리감지'],
            ['color' => 'RED', 'description' => 'PIR 이상'],
            ['color' => 'BLACK', 'description' => '정비단'],
        ])->map(function ($item) use ($counts) {
            // Get count from our collection, default to 0 if color isn't present
            $item['count'] = $counts->get($item['color'], 0);
            return $item;
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'logDetails' => $logDetails
        ], 200);
    }
}

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

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }


}

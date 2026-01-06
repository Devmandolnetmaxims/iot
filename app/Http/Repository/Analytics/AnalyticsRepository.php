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

        $dateTime = $request['date'] . ' ' . $request['time'];

        // Check if this clock_time exists at all
        $exists = DB::table('device_6h_analytics')
            ->where('clock_time', $dateTime)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => true,
                'data'    => [],
                'message' => 'No data for this time window'
            ], 200);
        }

        // Fetch colors directly
        $data = DB::table('device_6h_analytics')
            ->where('clock_time', $dateTime)
            ->select(
                'device',
                'final_color'
            )
            ->orderBy('device')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

}

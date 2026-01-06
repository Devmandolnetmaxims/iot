<?php

namespace App\Http\Repository\Device;

use App\Models\Device;
use App\Models\TestMuguhwa;
use App\Constants\ApiMessages;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class DeviceRepository
{
    use ApiResponseTrait;

    // public static function Index($request)
    // {
    //     $self = new self;
    //     $query = Device::query();

    //     // 🔹 Global search across DEVICE, CAR, CAR_LINK
    //     if ($request->filled('search')) {
    //         $search = $request->search;
    //         $query->where(function ($q) use ($search) {
    //             $q->where('DEVICE', 'LIKE', "%{$search}%")
    //             ->orWhere('CAR', 'LIKE', "%{$search}%")
    //             ->orWhere('CAR_LINK', 'LIKE', "%{$search}%");
    //         });
    //     }

    //     // 🔹 Filters
    //     if ($request->filled('car')) {
    //         $query->where('CAR', $request->car);
    //     }
    //     if ($request->filled('type')) {
    //         $query->where('TYPE', $request->type);
    //     }
    //     if ($request->filled('car_link')) {
    //         $query->where('CAR_LINK', $request->car_link);
    //     }

    //     // 🔹 Date filters
    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('INSTALL', [$request->start_date, $request->end_date]);
    //     } elseif ($request->filled('INSTALL')) {
    //         $query->whereDate('INSTALL', $request->INSTALL);
    //     }

    //     // 🔹 Partial matches
    //     if ($request->filled('p1')) {
    //         $query->where('P1', 'LIKE', "%{$request->p1}%");
    //     }
    //     if ($request->filled('p2')) {
    //         $query->where('P2', 'LIKE', "%{$request->p2}%");
    //     }

    //     // 🔹 Pagination
    //     $perPage = $request->input('per_page', 10);
    //     $page = $request->input('page', 1);

    //     $devices = $query->paginate($perPage, ['*'], 'page', $page);

    //     // 🔹 Attach latest trigger data
    //     $devices->getCollection()->transform(function ($device) {
    //         $latestTrigger = TestMuguhwa::where('DEVICE', $device->DEVICE)
    //             ->orderBy('TIME', 'desc')
    //             ->first();

    //         $device->last_trigger = $latestTrigger;
    //         return $device;
    //     });

    //     // 🔹 Custom structured response
    //     $response = [
    //         'data' => $devices->items(),
    //         'pagination' => [
    //             'page' => $devices->currentPage(),
    //             'per_page' => $devices->perPage(),
    //             'total' => $devices->total(),
    //             'last_page' => $devices->lastPage(),
    //         ]
    //     ];

    //     return $self->successResponse($response, ApiMessages::DEVICE_GET_SUCCESS, 200);
    // }

    // public static function Index($request)
    // {

    //     $query = Device::query();

    //     // ✅ Global search
    //     $query->when($request->search, function ($q, $search) {
    //         $q->where(function ($sub) use ($search) {
    //             $sub->where('DEVICE', 'LIKE', "%{$search}%")
    //                 ->orWhere('CAR', 'LIKE', "%{$search}%")
    //                 ->orWhere('CAR_LINK', 'LIKE', "%{$search}%");
    //         });
    //     });

    //     // ✅ Filters
    //     $query->when($request->car, fn($q, $car) => $q->where('CAR', $car))
    //         ->when($request->type, fn($q, $type) => $q->where('TYPE', $type))
    //         ->when($request->car_link, fn($q, $link) => $q->where('CAR_LINK', $link));

    //     // ✅ Date filters
    //     $query->when($request->start_date && $request->end_date, function ($q) use ($request) {
    //         $q->whereBetween('INSTALL', [$request->start_date, $request->end_date]);
    //     })->when($request->INSTALL, function ($q, $install) {
    //         $q->whereDate('INSTALL', $install);
    //     });

    //     // ✅ Partial match filters
    //     $query->when($request->p1, fn($q, $p1) => $q->where('P1', 'LIKE', "%{$p1}%"))
    //         ->when($request->p2, fn($q, $p2) => $q->where('P2', 'LIKE', "%{$p2}%"));

    //     // ✅ Pagination
    //     $perPage = $request->input('per_page', 10);

    //     // ✅ Eager load latest trigger (NO LOOP, NO N+1)
    //     $devices = $query
    //         ->with('lastTrigger')
    //         ->paginate($perPage);

    //     // ✅ Custom structured response
    //     $response = [
    //         'data' => $devices->items(),
    //         'pagination' => [
    //             'page' => $devices->currentPage(),
    //             'per_page' => $devices->perPage(),
    //             'total' => $devices->total(),
    //             'last_page' => $devices->lastPage(),
    //         ]
    //     ];

    //     return (new self)->successResponse(
    //         $response,
    //         ApiMessages::DEVICE_GET_SUCCESS,
    //         200
    //     );
    // }

    public static function Index($request)
    {
        $perPage = min((int) $request->input('per_page', 10), 300);

        // 1️⃣ Subquery: latest TIME per DEVICE
        $latestTime = DB::table('TestMuguhwa')
            ->select('DEVICE', DB::raw('MAX(TIME) as TIME'))
            ->groupBy('DEVICE');

        // 2️⃣ Main query
        $query = DB::table('DeviceInfo2 as d')
            ->leftJoinSub($latestTime, 'lt', function ($join) {
                $join->on('lt.DEVICE', '=', 'd.DEVICE');
            })
            ->leftJoin('TestMuguhwa as t', function ($join) {
                $join->on('t.DEVICE', '=', 'lt.DEVICE')
                    ->on('t.TIME', '=', 'lt.TIME');
            })
            ->select([
                'd.DEVICE',
                'd.CAR',
                'd.TYPE',
                'd.INSTALL',
                'd.CAR_LINK',
                'd.P1',
                'd.P2',
                'd.created_at',
                'd.updated_at',
                'd.deleted_at',

                // trigger columns (flat)
                't.DEVICE as t_DEVICE',
                't.TIME as t_TIME',
                't.BEGIN',
                't.LAST',
                't.EVENT',
                't.ACTIVE',
                't.PIR',
                't.TOF',
                't.UV',
                't.MM',
                't.TEMP',
            ]);

        /* ---------- filters (unchanged) ---------- */

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('d.DEVICE', 'LIKE', "%{$s}%")
                ->orWhere('d.CAR', 'LIKE', "%{$s}%")
                ->orWhere('d.CAR_LINK', 'LIKE', "%{$s}%");
            });
        }

        $query->when($request->car, fn ($q, $v) => $q->where('d.CAR', $v))
            ->when($request->type, fn ($q, $v) => $q->where('d.TYPE', $v))
            ->when($request->car_link, fn ($q, $v) => $q->where('d.CAR_LINK', $v))
            ->when($request->INSTALL, fn ($q, $v) => $q->whereDate('d.INSTALL', $v))
            ->when($request->p1, fn ($q, $v) => $q->where('d.P1', 'LIKE', "%{$v}%"))
            ->when($request->p2, fn ($q, $v) => $q->where('d.P2', 'LIKE', "%{$v}%"));

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('d.INSTALL', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // 3️⃣ Pagination
        $page = $query->paginate($perPage);

        // 4️⃣ RESHAPE to original format
        $data = collect($page->items())->map(function ($row) {
            return [
                'DEVICE'      => $row->DEVICE,
                'CAR'         => $row->CAR,
                'TYPE'        => $row->TYPE,
                'INSTALL'     => $row->INSTALL,
                'CAR_LINK'    => $row->CAR_LINK,
                'P1'          => $row->P1,
                'P2'          => $row->P2,
                'created_at'  => $row->created_at,
                'updated_at'  => $row->updated_at,
                'deleted_at'  => $row->deleted_at,

                // 🔥 SAME FORMAT AS BEFORE
                'last_trigger' => $row->t_TIME ? [
                    'DEVICE' => $row->t_DEVICE,
                    'TIME'   => $row->t_TIME,
                    'BEGIN'  => $row->BEGIN,
                    'LAST'   => $row->LAST,
                    'EVENT'  => $row->EVENT,
                    'ACTIVE' => $row->ACTIVE,
                    'PIR'    => $row->PIR,
                    'TOF'    => $row->TOF,
                    'UV'     => $row->UV,
                    'MM'     => $row->MM,
                    'TEMP'   => $row->TEMP,
                ] : null,
            ];
        });

        return (new self)->successResponse([
            'data' => $data,
            'pagination' => [
                'page'      => $page->currentPage(),
                'per_page'  => $page->perPage(),
                'total'     => $page->total(),
                'last_page' => $page->lastPage(),
            ]
        ], ApiMessages::DEVICE_GET_SUCCESS);
    }

    public static function getDeviceById($id)
    {
        try {
            $device = Device::find($id);

            if (!$device) {
                return response()->json([
                    'status' => false,
                    'message' => 'Device not found.',
                ], 404);
            }

            // 🔹 Pick the REAL latest trigger
            $latestTrigger = TestMuguhwa::where('DEVICE', $device->DEVICE)
                // ->latest('TIME')   // ensures newest row by TIME
                ->orderBy('TIME', 'desc')
                ->first();

            $device->last_trigger = $latestTrigger;

            return response()->json([
                'status' => true,
                'message' => 'Device fetched successfully.',
                'data' => $device,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public static function Create($request)
    {
        $self = new self;
        // Create a new record
        $device = Device::create($request->all());

        return $self->successResponse($device, ApiMessages::DEVICE_CREATED, 201);
    }

    public static function Update($request, $id)
    {
        $self = new self;

        // Find the existing record by DEVICE (or whichever key identifies it)
        $device = Device::where('DEVICE', $id)->first();

        if (! $device) {
            return $self->errorResponse(null, ApiMessages::DEVICE_NOT_FOUND, 404);
        }

        // Exclude DEVICE from update payload
        $updateData = $request->except(['DEVICE']);

        // Update only other fields
        $device->update($updateData);

        return $self->successResponse($device, ApiMessages::DEVICE_UPDATED, 200);
    }

    public static function Delete($id){
        $self = new self;

        // Find the existing record by DEVICE (or whichever key identifies it)
        $device = Device::where('DEVICE', $id)->first();

        if (! $device) {
            return $self->errorResponse(null, ApiMessages::DEVICE_NOT_FOUND, 404);
        }

        // Delete the record
        $device->delete();

        return $self->successResponse(null, ApiMessages::DEVICE_DELETED, 200);
    }
}

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
    //     $perPage = min((int) $request->input('per_page', 10), 300);

    //     // 1️⃣ Subquery: latest TIME per DEVICE
    //     // $latestTime = DB::table('TestMuguhwa')
    //     //     ->select('DEVICE', DB::raw('MAX(TIME) as TIME'))
    //     //     ->groupBy('DEVICE');
    //     $latestTime = DB::table('TestMuguhwa')
    //     ->select([
    //         'DEVICE',
    //         'TIME',
    //         DB::raw('MAX(BEGIN) as BEGIN'),
    //         DB::raw('MAX(LAST) as LAST'),
    //         DB::raw('MAX(EVENT) as EVENT'),
    //         DB::raw('MAX(ACTIVE) as ACTIVE'),
    //         DB::raw('MAX(PIR) as PIR'),
    //         DB::raw('MAX(TOF) as TOF'),
    //         DB::raw('MAX(UV) as UV'),
    //         DB::raw('MAX(MM) as MM'),
    //         DB::raw('MAX(TEMP) as TEMP'),
    //     ])
    //     ->groupBy('DEVICE', 'TIME');

    //     $latestTime = DB::table('TestMuguhwa')
    //         ->select('DEVICE', DB::raw('MAX(TIME) as TIME'))
    //         ->groupBy('DEVICE');

    //     // 2️⃣ Main query
    //     $query = DB::table('DeviceInfo2 as d')
    //         ->leftJoinSub($latestTime, 'lt', function ($join) {
    //             $join->on('lt.DEVICE', '=', 'd.DEVICE');
    //         })
    //         ->leftJoin('TestMuguhwa as t', function ($join) {
    //             $join->on('t.DEVICE', '=', 'lt.DEVICE')
    //                 ->on('t.TIME', '=', 'lt.TIME');
    //         })
    //         ->select([
    //             'd.DEVICE',
    //             'd.CAR',
    //             'd.TYPE',
    //             'd.INSTALL',
    //             'd.CAR_LINK',
    //             'd.P1',
    //             'd.P2',
    //             'd.created_at',
    //             'd.updated_at',
    //             'd.deleted_at',

    //             // trigger columns (flat)
    //             't.DEVICE as t_DEVICE',
    //             't.TIME as t_TIME',
    //             't.BEGIN',
    //             't.LAST',
    //             't.EVENT',
    //             't.ACTIVE',
    //             't.PIR',
    //             't.TOF',
    //             't.UV',
    //             't.MM',
    //             't.TEMP',
    //         ]);

    //     /* ---------- filters (unchanged) ---------- */

    //     if ($request->filled('search')) {
    //         $s = $request->search;
    //         $query->where(function ($q) use ($s) {
    //             $q->where('d.DEVICE', 'LIKE', "%{$s}%")
    //             ->orWhere('d.CAR', 'LIKE', "%{$s}%")
    //             ->orWhere('d.CAR_LINK', 'LIKE', "%{$s}%");
    //         });
    //     }

    //     $query->when($request->car, fn ($q, $v) => $q->where('d.CAR', $v))
    //         ->when($request->type, fn ($q, $v) => $q->where('d.TYPE', $v))
    //         ->when($request->car_link, fn ($q, $v) => $q->where('d.CAR_LINK', $v))
    //         ->when($request->INSTALL, fn ($q, $v) => $q->whereDate('d.INSTALL', $v))
    //         ->when($request->p1, fn ($q, $v) => $q->where('d.P1', 'LIKE', "%{$v}%"))
    //         ->when($request->p2, fn ($q, $v) => $q->where('d.P2', 'LIKE', "%{$v}%"));

    //     if ($request->start_date && $request->end_date) {
    //         $query->whereBetween('d.INSTALL', [
    //             $request->start_date,
    //             $request->end_date
    //         ]);
    //     }

    //     /* ---------- Sorting Logic ---------- */

    //     // Default sort if no parameters are provided
    //     $sortColumn = $request->input('sort_by', 'd.DEVICE'); // Options: device, install, last_trigger, CAR
    //     $sortOrder = $request->input('sort_order', 'asc');   // Options: asc, desc

    //     // Map request keys to actual database columns
    //     switch ($sortColumn) {
    //         case 'install':
    //             $query->orderBy('d.INSTALL', $sortOrder);
    //             break;
    //         case 'last_trigger':
    //             // We sort by the TIME column joined from the TestMuguhwa table
    //             $query->orderBy('t.TIME', $sortOrder);
    //             break;
    //         case 'device':
    //             $query->orderBy('d.DEVICE', $sortOrder);
    //             break;
    //         case 'car':
    //             $query->orderBy('d.CAR', $sortOrder);
    //             break;
    //         default:
    //             $query->orderBy('d.DEVICE', $sortOrder);
    //             break;
    //     }

    //     // 3️⃣ Pagination
    //     $page = $query->paginate($perPage);

    //     // 4️⃣ RESHAPE to original format
    //     $data = collect($page->items())->map(function ($row) {
    //         return [
    //             'DEVICE'      => $row->DEVICE,
    //             'CAR'         => $row->CAR,
    //             'TYPE'        => $row->TYPE,
    //             'INSTALL'     => $row->INSTALL,
    //             'CAR_LINK'    => $row->CAR_LINK,
    //             'P1'          => $row->P1,
    //             'P2'          => $row->P2,
    //             'created_at'  => $row->created_at,
    //             'updated_at'  => $row->updated_at,
    //             'deleted_at'  => $row->deleted_at,

    //             // 🔥 SAME FORMAT AS BEFORE
    //             'last_trigger' => $row->t_TIME ? [
    //                 'DEVICE' => $row->t_DEVICE,
    //                 'TIME'   => $row->t_TIME,
    //                 'BEGIN'  => $row->BEGIN,
    //                 'LAST'   => $row->LAST,
    //                 'EVENT'  => $row->EVENT,
    //                 'ACTIVE' => $row->ACTIVE,
    //                 'PIR'    => $row->PIR,
    //                 'TOF'    => $row->TOF,
    //                 'UV'     => $row->UV,
    //                 'MM'     => $row->MM,
    //                 'TEMP'   => $row->TEMP,
    //             ] : null,
    //         ];
    //     });

    //     return (new self)->successResponse([
    //         'data' => $data,
    //         'pagination' => [
    //             'page'      => $page->currentPage(),
    //             'per_page'  => $page->perPage(),
    //             'total'     => $page->total(),
    //             'last_page' => $page->lastPage(),
    //         ]
    //     ], ApiMessages::DEVICE_GET_SUCCESS);
    // }

    // public static function Index($request)
    // {
    //     $perPage = min((int) $request->input('per_page', 10), 300);

    //     // 1️⃣ Subquery: Find the absolute latest timestamp for each device
    //     $latestTime = DB::table('TestMuguhwa')
    //         ->select('DEVICE', DB::raw('MAX(TIME) as TIME'))
    //         ->groupBy('DEVICE');

    //     // 2️⃣ Main query
    //     $query = DB::table('DeviceInfo2 as d')
    //         ->leftJoinSub($latestTime, 'lt', function ($join) {
    //             $join->on('lt.DEVICE', '=', 'd.DEVICE');
    //         })
    //         ->leftJoin('TestMuguhwa as t', function ($join) {
    //             $join->on('t.DEVICE', '=', 'lt.DEVICE')
    //                 ->on('t.TIME', '=', 'lt.TIME');
    //         })
    //         ->select([
    //             'd.DEVICE',
    //             // Use ANY_VALUE for all columns to collapse duplicates into 1 row
    //             DB::raw('ANY_VALUE(d.CAR) as CAR'),
    //             DB::raw('ANY_VALUE(d.TYPE) as TYPE'),
    //             DB::raw('ANY_VALUE(d.INSTALL) as INSTALL'),
    //             DB::raw('ANY_VALUE(d.CAR_LINK) as CAR_LINK'),
    //             DB::raw('ANY_VALUE(d.P1) as P1'),
    //             DB::raw('ANY_VALUE(d.P2) as P2'),
    //             DB::raw('ANY_VALUE(d.created_at) as created_at'),
    //             DB::raw('ANY_VALUE(d.updated_at) as updated_at'),
    //             DB::raw('ANY_VALUE(d.deleted_at) as deleted_at'),
    //             DB::raw('ANY_VALUE(t.DEVICE) as t_DEVICE'),
    //             DB::raw('ANY_VALUE(t.TIME) as t_TIME'),
    //             // We take the MAX of these to ensure only one of the competing logs is picked
    //             DB::raw('MAX(t.BEGIN) as BEGIN'),
    //             DB::raw('MAX(t.LAST) as LAST'),
    //             DB::raw('MAX(t.EVENT) as EVENT'),
    //             DB::raw('MAX(t.ACTIVE) as ACTIVE'),
    //             DB::raw('ANY_VALUE(t.PIR) as PIR'),
    //             DB::raw('ANY_VALUE(t.TOF) as TOF'),
    //             DB::raw('ANY_VALUE(t.UV) as UV'),
    //             DB::raw('ANY_VALUE(t.MM) as MM'),
    //             DB::raw('ANY_VALUE(t.TEMP) as TEMP'),
    //         ])
    //         ->groupBy('d.DEVICE'); // 🔥 This forces the one-row-per-device rule

    //     /* ---------- Filters (Unchanged) ---------- */
    //     if ($request->filled('search')) {
    //         $s = $request->search;
    //         $query->where(function ($q) use ($s) {
    //             $q->where('d.DEVICE', 'LIKE', "%{$s}%")
    //             ->orWhere('d.CAR', 'LIKE', "%{$s}%")
    //             ->orWhere('d.CAR_LINK', 'LIKE', "%{$s}%");
    //         });
    //     }

    //     $query->when($request->car, fn ($q, $v) => $q->where('d.CAR', $v))
    //         ->when($request->type, fn ($q, $v) => $q->where('d.TYPE', $v))
    //         ->when($request->car_link, fn ($q, $v) => $q->where('d.CAR_LINK', $v))
    //         ->when($request->INSTALL, fn ($q, $v) => $q->whereDate('d.INSTALL', $v))
    //         ->when($request->p1, fn ($q, $v) => $q->where('d.P1', 'LIKE', "%{$v}%"))
    //         ->when($request->p2, fn ($q, $v) => $q->where('d.P2', 'LIKE', "%{$v}%"));

    //     if ($request->start_date && $request->end_date) {
    //         $query->whereBetween('d.INSTALL', [$request->start_date, $request->end_date]);
    //     }

    //     /* ---------- Sorting Logic ---------- */
    //     $sortColumn = $request->input('sort_by', 'd.DEVICE');
    //     $sortOrder = $request->input('sort_order', 'asc');

    //     // Important: When using Group By, sort by the raw column or aggregated alias
    //     if ($sortColumn == 'last_trigger') {
    //         $query->orderBy('t_TIME', $sortOrder);
    //     } elseif ($sortColumn == 'install') {
    //         $query->orderBy('INSTALL', $sortOrder);
    //     } elseif ($sortColumn == 'car') {
    //         $query->orderBy('CAR', $sortOrder);
    //     } else {
    //         $query->orderBy('d.DEVICE', $sortOrder);
    //     }

    //     // 3️⃣ Pagination
    //     $page = $query->paginate($perPage);

    //     // 4️⃣ Reshape (Same as before)
    //     $data = collect($page->items())->map(function ($row) {
    //         return [
    //             'DEVICE'       => $row->DEVICE,
    //             'CAR'          => $row->CAR,
    //             'TYPE'         => $row->TYPE,
    //             'INSTALL'      => $row->INSTALL,
    //             'CAR_LINK'     => $row->CAR_LINK,
    //             'P1'           => $row->P1,
    //             'P2'           => $row->P2,
    //             'created_at'   => $row->created_at,
    //             'updated_at'   => $row->updated_at,
    //             'deleted_at'   => $row->deleted_at,
    //             'last_trigger' => $row->t_TIME ? [
    //                 'DEVICE' => $row->t_DEVICE,
    //                 'TIME'   => $row->t_TIME,
    //                 'BEGIN'  => $row->BEGIN,
    //                 'LAST'   => $row->LAST,
    //                 'EVENT'  => $row->EVENT,
    //                 'ACTIVE' => $row->ACTIVE,
    //                 'PIR'    => $row->PIR,
    //                 'TOF'    => $row->TOF,
    //                 'UV'     => $row->UV,
    //                 'MM'     => $row->MM,
    //                 'TEMP'   => $row->TEMP,
    //             ] : null,
    //         ];
    //     });

    //     return (new self)->successResponse([
    //         'data' => $data,
    //         'pagination' => [
    //             'page'      => $page->currentPage(),
    //             'per_page'  => $page->perPage(),
    //             'total'     => $page->total(),
    //             'last_page' => $page->lastPage(),
    //         ]
    //     ], "Success");
    // }

     public static function Index($request)
    {
        $perPage = min((int) $request->input('per_page', 10), 300);

        // 1️⃣ Get the latest time per device
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
                // Using MAX() instead of ANY_VALUE() for compatibility
                DB::raw('MAX(d.CAR) as CAR'),
                DB::raw('MAX(d.TYPE) as TYPE'),
                DB::raw('MAX(d.INSTALL) as INSTALL'),
                DB::raw('MAX(d.CAR_LINK) as CAR_LINK'),
                DB::raw('MAX(d.P1) as P1'),
                DB::raw('MAX(d.P2) as P2'),
                DB::raw('MAX(d.created_at) as created_at'),
                DB::raw('MAX(d.updated_at) as updated_at'),
                DB::raw('MAX(d.deleted_at) as deleted_at'),
                DB::raw('MAX(t.DEVICE) as t_DEVICE'),
                DB::raw('MAX(t.TIME) as t_TIME'),
                DB::raw('MAX(t.BEGIN) as BEGIN'),
                DB::raw('MAX(t.LAST) as LAST'),
                DB::raw('MAX(t.EVENT) as EVENT'),
                DB::raw('MAX(t.ACTIVE) as ACTIVE'),
                DB::raw('MAX(t.PIR) as PIR'),
                DB::raw('MAX(t.TOF) as TOF'),
                DB::raw('MAX(t.UV) as UV'),
                DB::raw('MAX(t.MM) as MM'),
                DB::raw('MAX(t.TEMP) as TEMP'),
            ])
            ->groupBy('d.DEVICE'); // 🔥 This ensures only 1 row per device

        /* ---------- Filters ---------- */
        // Apply filters before the group by grouping happens if possible
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
            $query->whereBetween('d.INSTALL', [$request->start_date, $request->end_date]);
        }

        /* ---------- Sorting ---------- */
        $sortColumn = $request->input('sort_by', 'd.DEVICE');
        $sortOrder = $request->input('sort_order', 'asc');

        // Use the alias names for sorting after the aggregation
        if ($sortColumn == 'last_trigger') {
            $query->orderBy('t_TIME', $sortOrder);
        } elseif ($sortColumn == 'install') {
            $query->orderBy('INSTALL', $sortOrder);
        } elseif ($sortColumn == 'device') {
            $query->orderBy('d.DEVICE', $sortOrder);
        } elseif ($sortColumn == 'car') {
            $query->orderBy('d.CAR', $sortOrder);
        }

        // 3️⃣ Pagination
        $page = $query->paginate($perPage);

        // 4️⃣ Reshape (unchanged)
        $data = collect($page->items())->map(function ($row) {
            return [
                'DEVICE'       => $row->DEVICE,
                'CAR'          => $row->CAR,
                'TYPE'         => $row->TYPE,
                'INSTALL'      => $row->INSTALL,
                'CAR_LINK'     => $row->CAR_LINK,
                'P1'           => $row->P1,
                'P2'           => $row->P2,
                'created_at'   => $row->created_at,
                'updated_at'   => $row->updated_at,
                'deleted_at'   => $row->deleted_at,
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
        ], "Success");
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

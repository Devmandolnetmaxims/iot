<?php

namespace App\Http\Repository\Device;

use App\Models\Device;
use App\Models\TestMuguhwa;
use App\Constants\ApiMessages;
use App\Traits\ApiResponseTrait;


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

    public static function Index($request)
    {
        $query = Device::query();

        // ✅ Global search
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('DEVICE', 'LIKE', "%{$search}%")
                    ->orWhere('CAR', 'LIKE', "%{$search}%")
                    ->orWhere('CAR_LINK', 'LIKE', "%{$search}%");
            });
        });

        // ✅ Filters
        $query->when($request->car, fn($q, $car) => $q->where('CAR', $car))
            ->when($request->type, fn($q, $type) => $q->where('TYPE', $type))
            ->when($request->car_link, fn($q, $link) => $q->where('CAR_LINK', $link));

        // ✅ Date filters
        $query->when($request->start_date && $request->end_date, function ($q) use ($request) {
            $q->whereBetween('INSTALL', [$request->start_date, $request->end_date]);
        })->when($request->INSTALL, function ($q, $install) {
            $q->whereDate('INSTALL', $install);
        });

        // ✅ Partial match filters
        $query->when($request->p1, fn($q, $p1) => $q->where('P1', 'LIKE', "%{$p1}%"))
            ->when($request->p2, fn($q, $p2) => $q->where('P2', 'LIKE', "%{$p2}%"));

        // ✅ Pagination
        $perPage = $request->input('per_page', 10);

        // ✅ Eager load latest trigger (NO LOOP, NO N+1)
        $devices = $query
            ->with('lastTrigger')
            ->paginate($perPage);

        // ✅ Custom structured response
        $response = [
            'data' => $devices->items(),
            'pagination' => [
                'page' => $devices->currentPage(),
                'per_page' => $devices->perPage(),
                'total' => $devices->total(),
                'last_page' => $devices->lastPage(),
            ]
        ];

        return (new self)->successResponse(
            $response,
            ApiMessages::DEVICE_GET_SUCCESS,
            200
        );
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
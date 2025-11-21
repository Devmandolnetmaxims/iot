<?php

namespace App\Http\Repository\DeviceLink;

use App\Models\DeviceLink;
use App\Constants\ApiMessages;
use App\Traits\ApiResponseTrait;

class DeviceLinkRepository
{
    use ApiResponseTrait;
    
    public static function Index($request)
    {
        $self = new self;
        $query = DeviceLink::query();

        // 🔍 GLOBAL SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('D_Link', 'like', "%{$search}%")
                ->orWhere('P1', 'like', "%{$search}%")
                ->orWhere('P2', 'like', "%{$search}%");

                for ($i = 1; $i <= 8; $i++) {
                    $col = 'CAR' . $i;
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        // 📄 Pagination
        $page     = (int) $request->get('page', 1);
        $perPage  = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        // 🧩 Transform output (coach array with 8 values)
        $records = $paginated->getCollection()->map(function ($item) {

            // Always include 8 values, even if null/blank
            $coach = [];
            for ($i = 1; $i <= 8; $i++) {
                $col = 'CAR' . $i;
                $coach[] = $item->$col ?? null;   // keep null/blank
            }

            return [
                'D_Link' => $item->D_Link,
                'P1' => $item->P1,
                'P2' => $item->P2,
                'coach' => $coach,   // ✅ ARRAY, not object
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'deleted_at' => $item->deleted_at,
            ];
        });

        // 🧾 Final structured response
        $data = [
            'records' => $records,
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ]
        ];

        return $self->successResponse($data, ApiMessages::DEVICE_LINK_GET_SUCCESS, 200);
    }


    public static function CreateDeviceLink($data)
    {
        $self = new self;
        $validatedData = $data;
        try {
            // Create new record
            $deviceLink = DeviceLink::create($validatedData);

            return $self->successResponse($deviceLink, ApiMessages::DEVICE_LINK_CREATED, 201);
        } catch (\Exception $e) {
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

    // Update function
    public static function UpdateDeviceLink($id, $data)
    {
        $self = new self;

        try {
            // 🔍 Find existing record
            $deviceLink = DeviceLink::find($id);

            if (! $deviceLink) {
                return $self->errorResponse(null, ApiMessages::DEVICE_LINK_NOT_FOUND, 404);
            }

            // 🛠️ Update record
            $deviceLink->update($data);

            return $self->successResponse($deviceLink, ApiMessages::DEVICE_LINK_UPDATED, 200);
        } catch (\Exception $e) {
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

    // Delete function
    public static function DeleteDeviceLink($id)
    {
        $self = new self;

        try {
            // 🔍 Find existing record
            $deviceLink = DeviceLink::find($id);

            if (! $deviceLink) {
                return $self->errorResponse(null, ApiMessages::DEVICE_LINK_NOT_FOUND, 404);
            }

            // 🛠️ Delete record
            $deviceLink->delete();

            return $self->successResponse(null, ApiMessages::DEVICE_LINK_DELETED, 200);
        } catch (\Exception $e) {
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

    // Get all car numbers
    public static function GetAllCars()
    {
        $self = new self;

        $records = DeviceLink::select([
            'CAR1', 'CAR2', 'CAR3', 'CAR4', 'CAR5', 'CAR6', 'CAR7', 'CAR8'
        ])->get()
        ->toArray(); // ✅ Convert to plain arrays

        // 🔹 Flatten all car numbers into a single array
        $cars = collect($records)
            ->flatMap(function ($item) {
                return collect($item)
                    ->filter(fn($v) => !empty($v)) // remove null or empty
                    ->values();
            })
            ->unique()
            ->values();

        return $self->successResponse($cars, 'All car numbers fetched successfully', 200);
    }


}
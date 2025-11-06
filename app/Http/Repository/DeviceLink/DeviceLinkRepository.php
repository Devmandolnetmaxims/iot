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

        // 🔍 GLOBAL SEARCH across all columns
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

        // 🧩 Structured response (numeric pagination, no links)
        $data = [
            'records' => $paginated->items(),
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

}
<?php

namespace App\Http\Repository\DataLog;

use App\Models\Testmuguhwa;
use App\Constants\ApiMessages;
use App\Traits\ApiResponseTrait;

class DataLogRepository
{
    use ApiResponseTrait;

    public static function Index($request)
    {
        $self = new self;
        $query = Testmuguhwa::query();

        // 🔍 Optional filter by DEVICE
        if ($request->filled('device')) {
            $query->where('DEVICE', $request->device);
        }

        // Optional date range filter (for later use)
        // if ($request->filled('from') && $request->filled('to')) {
        //     $query->whereBetween('TIME', [$request->from, $request->to]);
        // }

        // Pagination setup
        $page = max((int) $request->get('page', 1), 1);
        $perPage = max((int) $request->get('per_page', 10), 10);

        $total = $query->count();
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        $data = $query->orderBy('TIME', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $self->successResponse([
            'datalog' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage
            ],
        ], ApiMessages::DATA_LOG_GET_SUCCESS, 200);
    }

}
<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function successResponse($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => true,
        ], $code);
    }

    protected function errorResponse($data = null, string $message = 'Error', int $code = 400)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => false,
        ], $code);
    }
}

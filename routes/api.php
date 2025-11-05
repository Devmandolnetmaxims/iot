<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Device\DeviceController;


// Authentication routes
Route::post('/login', [AuthController::class, 'login']); 
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'ResetPassword']);


// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);

    // Device routes
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'index']);
        Route::post('/', [DeviceController::class, 'store']);
        Route::get('/{id}', [DeviceController::class, 'show']);
        Route::put('/{id}', [DeviceController::class, 'update']);
        Route::delete('/{id}', [DeviceController::class, 'destroy']);
    });
});


// Default authenticated route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

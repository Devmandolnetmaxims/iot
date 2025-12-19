<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Device\DeviceController;
use App\Http\Controllers\DataLog\DataLogController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\DeviceLink\DeviceLinkController;


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

    // DeviceLink routes
    Route::prefix('devicelinks')->group(function () {
       Route::get('/', [DeviceLinkController::class, 'index']);
       Route::post('/', [DeviceLinkController::class, 'store']);
       Route::get('/cars', [DeviceLinkController::class, 'getAllCars']);
       Route::get('/{id}', [DeviceLinkController::class, 'show']);
       Route::put('/{id}', [DeviceLinkController::class, 'update']);
       Route::delete('/{id}', [DeviceLinkController::class, 'destroy']);
    });

    // DataLog routes
    Route::prefix('datalogs')->group(function () {
        Route::get('/', [DataLogController::class, 'index']);
    });


    // Analytics routes
    Route::prefix('analytics')->group(function () {
        Route::post('/load6hrawdata', [AnalyticsController::class, 'load6hrawdata']);
        Route::get('/calculatErrorState', [AnalyticsController::class, 'calculatErrorState']);
    });
});


// Default authenticated route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/speed',function() {
    return "hasds";
});

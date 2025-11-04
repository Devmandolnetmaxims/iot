<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;


// Authentication routes
Route::post('/login', [AuthController::class, 'login']); // Send OTP route
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // Verify OTP route


// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

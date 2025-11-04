<?php

namespace App\Http\Controllers\Auth;

use App\Http\Repository\Auth\AuthRepository;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Login
    public function login(LoginRequest $request) {
        return AuthRepository::Login($request);
    }
}

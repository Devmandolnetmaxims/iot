<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\ForgetEmailRequest;
use App\Http\Repository\Auth\AuthRepository;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    // Login
    public function login(LoginRequest $request) {
        return AuthRepository::Login($request);
    }

    // Logout
    public function logout(Request $request) {
        return AuthRepository::Logout($request);
    }

    // Forgot password
    public function forgotPassword(ForgetEmailRequest $request) {
        return AuthRepository::ForgotPassword($request);
    }

    // Reset password
    public function ResetPassword(Request $request) {
        return AuthRepository::resetPassword($request);
    }
}

<?php

namespace App\Http\Repository\Auth;

use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Traits\ApiResponseTrait;
use App\Constants\ApiMessages;
use App\Models\User;

class AuthRepository
{
    use ApiResponseTrait;
    // Register new user
   public static function Login($request) 
    {
        $self = new self;
        // Find user by email
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $self->errorResponse(null, ApiMessages::INVALID_CREDENTIALS, 401);
        }

        if (! $user->hasRole('admin')) {
            return $self->errorResponse(null, ApiMessages::UNAUTHORIZED_ADMIN, 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return $self->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => ucfirst($user->name),
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->first(),
                'token' => $token
            ],
        ], ApiMessages::LOGIN_SUCCESS, 200);
    }

    public static function Logout($request)
    {
        try {
            $self = new self;

            $user = $request->user();

            if (! $user) {
                return $self->errorResponse(null, ApiMessages::USER_NOT_FOUND, 404);
            }

            // Revoke current access token
            $user->currentAccessToken()->delete();

            return $self->successResponse(null, ApiMessages::LOGOUT_SUCCESS, 200);
        } catch (\Exception $e) {
            return $self->errorResponse(null, ApiMessages::LOGOUT_ERROR, 500);
        }
        
    }

}

<?php

namespace App\Http\Repository\Auth;

use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AuthRepository
{
    // Register new user
   public static function Login($request) {
        // Find user by email
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'data' => null,
                'message' => 'Invalid Credentials',
                'status' => false
            ], 401);
        }

        if (! $user->hasRole('admin')) {
            return response()->json([
                'data' => null,
                'message' => 'Unauthorized: Not an admin',
                'status' => false
            ], 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => ucfirst($user->name), // first letter capital
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')
                ],
                'token' => $token
            ],
            'message' => 'Login Successful',
            'status' => true
        ], 200);
    }
}
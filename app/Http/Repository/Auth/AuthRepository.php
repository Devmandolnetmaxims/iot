<?php

namespace App\Http\Repository\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Constants\ApiMessages;
use App\Helpers\MailHelper;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class AuthRepository
{
    use ApiResponseTrait;
    // Register new user
    public static function Login($request)
    {
        $self = new self;
        // Find user by email
        $user = User::with(['roles','userDetails'])->where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $self->errorResponse(null, ApiMessages::INVALID_CREDENTIALS, 401);
        }

        if (! $user->hasRole('admin')) {
            // return $self->errorResponse(null, ApiMessages::UNAUTHORIZED_ADMIN, 403);
            // check for status from user details
            $status = $user->userDetails->status;
            if ($status == 0) {
                return $self->errorResponse(null, ApiMessages::INACTIVE_ACCOUNT, 403);
            }
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

    public static function ForgotPassword($request)
    {
        $self = new self;
        $email = $request->email;
        $user = User::where('email', $email)->first();

        if (!$user) {
            return  $self->errorResponse(null, ApiMessages::EMAIL_NOT_FOUND, 404);
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $resetUrl = url("/reset-password?token={$token}");

        MailHelper::sendMail($email, 'Reset Your Password', 'emails.reset_password', [
            'resetUrl' => $resetUrl,
        ]);

        return $self->successResponse(null, ApiMessages::RESET_LINK_SENT, 200);
    }

    public static function ResetPassword($request)
    {
        $self = new self;

        // Find the reset token record
        $record = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (! $record) {
            return $self->errorResponse(null, ApiMessages::INVALID_TOKEN, 400);
        }

        // Find the user linked to this token
        $user = User::where('email', $record->email)->first();

        if (! $user) {
            return $self->errorResponse(null, ApiMessages::EMAIL_NOT_FOUND, 404);
        }

        // Update password
        $user->update(['password' => bcrypt($request->password)]);

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        return $self->successResponse(null, ApiMessages::PASSWORD_RESET_SUCCESS, 200);
    }

    public static function Profile($request)
    {
        $self = new self;
        $user = $request->user();
        return $self->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => ucfirst($user->name),
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->first(),
            ],
        ], ApiMessages::PROFILE_SUCCESS, 200);
    }
}

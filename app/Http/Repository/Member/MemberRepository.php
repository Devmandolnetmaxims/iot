<?php

namespace App\Http\Repository\Member;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Constants\ApiMessages;
use App\Models\UserDetail;
use App\Models\User;
use Exception;

class MemberRepository
{
    use ApiResponseTrait;
    public static function register($request)
    {
        $self = new self;

        // 1. Start the Transaction
        DB::beginTransaction();

        try {
            // 2. Create the base User
            // Note: Corrected $request to $request->email
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // 3. Create linked User Details
            $user->userDetails()->create([
                'phone_number' => $request->phone_number,
                'address'      => $request->address
            ]);

            // 4. Assign Role
            $user->assignRole('member');

            // 5. Commit changes if everything worked
            DB::commit();

            return $self->successResponse($user, ApiMessages::REGISTRATION_SUCCESS, 200);

        } catch (Exception $e) {
            // 6. Rollback database to original state on failure
            DB::rollBack();

            // Log the error for debugging
            Log::error("Registration Failed: " . $e->getMessage());

            return $self->errorResponse(
                null,
                $e->getMessage(),
                500
            );
        }
    }

    public static function MemberLists($request)
    {
        $self = new self;

        try {
            $query = User::with(['userDetails', 'roles']);

            // Search logic
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('userDetails', function ($subQuery) use ($search) {
                        $subQuery->where('phone_number', 'LIKE', "%{$search}%");
                    });
                });
            }

            // 1. Get the paginated collection
            $paginated = $query->latest()->paginate($request->per_page ?? 10);

            // 1. Transform the collection to clean up roles
            $transformedUsers = $paginated->getCollection()->map(function ($user) {
                return [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'details'      => $user->userDetails,
                    // Clean roles: only return id and name
                    'roles'        => $user->roles->map(function ($role) {
                        return [
                            'id'   => $role->id,
                            'name' => $role->name,
                        ];
                    }),
                ];
            });

            // 2. Custom format the pagination data
            $result = [
                'members' => $transformedUsers, // The actual user list
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
            ];

            return $self->successResponse($result, ApiMessages::USER_GET_SUCCESS, 200);

        } catch (Exception $e) {
            Log::error("User List Fetch Failed: " . $e->getMessage());
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

    public static function MemberDetail($id)
    {
        $self = new self;

        try {
            // 1. Find user with relationships or throw 404
            $user = User::with(['userDetails', 'roles'])->findOrFail($id);

            // 2. Format the response data (matching your list format)
            $data = [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'details' => $user->userDetails ? [
                    'phone_number' => $user->userDetails->phone_number,
                    'address'      => $user->userDetails->address,
                ] : null,
                'roles'   => $user->roles->map(function ($role) {
                    return [
                        'id'   => $role->id,
                        'name' => $role->name,
                    ];
                }),
            ];

            return $self->successResponse($data, ApiMessages::USER_GET_SUCCESS, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Specifically handle if the ID is wrong
            return $self->errorResponse(null, ApiMessages::USER_NOT_FOUND, 404);

        } catch (Exception $e) {
            Log::error("Fetching individual member failed: " . $e->getMessage());
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

    public static function Memberupdate($request, $id)
    {
        $self = new self;

        // 1. Start the Transaction
        DB::beginTransaction();

        try {
            // 2. Find the User
            $user = User::findOrFail($id);

            // 3. Update the base User
            $user->update([
                'name'  => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
            ]);

            // 4. Update password only if provided
            if ($request->filled('password')) {
                $user->update([
                    'password' => bcrypt($request->password)
                ]);
            }

            // 5. Update or Create linked User Details
            // updateOrCreate ensures the code doesn't crash if details didn't exist
            $user->userDetails()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone_number' => $request->phone_number ?? $user->userDetails->phone_number,
                    'address'      => $request->address ?? $user->userDetails->address
                ]
            );

            // 6. Refresh the model to get latest changes (including roles)
            $user->load(['userDetails', 'roles']);

            DB::commit();

            // 7. Prepare Clean Response
            $responseData = [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'details' => [
                    'phone_number' => $user->userDetails->phone_number,
                    'address'      => $user->userDetails->address,
                ],
                'roles'   => $user->roles->map(function ($role) {
                    return [
                        'id'   => $role->id,
                        'name' => $role->name,
                    ];
                }),
            ];

            return $self->successResponse($responseData, ApiMessages::USER_UPDATED, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("User Update Failed: " . $e->getMessage());

            return $self->errorResponse(
                null,
                $e->getMessage(),
                500
            );
        }
    }

    public static function MemberStateUpdate($request, $id)
    {
        $self = new self;
        try {
            $user = UserDetail::where('user_id', $id)->firstOrFail();
            $user->update(['status' => $request->status]);
            return $self->successResponse(null, ApiMessages::USER_UPDATED, 200);
        } catch (Exception $e) {
            Log::error("User State Update Failed: " . $e->getMessage());
            return $self->errorResponse(null, $e->getMessage(), 500);
        }
    }

}

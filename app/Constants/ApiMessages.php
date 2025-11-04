<?php

namespace App\Constants;

class ApiMessages
{
    // Auth messages
    const LOGIN_SUCCESS = 'Login successful';
    const LOGOUT_SUCCESS = 'Logout successful';
    const INVALID_CREDENTIALS = 'Invalid credentials';
    const UNAUTHORIZED_ADMIN = 'Unauthorized: Not an admin';

    // General messages
    const SUCCESS = 'Success';
    const FAILED = 'Something went wrong';
    const VALIDATION_ERROR = 'Validation errors';

    // Token / Permission messages
    const TOKEN_EXPIRED = 'Token expired';
    const UNAUTHORIZED = 'Unauthorized access';

    // Example: user actions
    const USER_NOT_FOUND = 'User not found';
    const USER_CREATED = 'User created successfully';
    const USER_UPDATED = 'User updated successfully';
    const USER_DELETED = 'User deleted successfully';
}
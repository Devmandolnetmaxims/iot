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
    const USER_GET_SUCCESS = 'User fetched successfully';

    // Example: password reset
    public const EMAIL_NOT_FOUND = 'No user found with this email.';
    public const MAIL_SEND_FAILED = 'Unable to send email, please try again.';
    public const OTP_SENT = 'OTP sent successfully to your email.';
    public const INVALID_OTP = 'Invalid or expired OTP.';
    public const OTP_VERIFIED = 'OTP verified successfully.';
    public const PASSWORD_RESET_SUCCESS = 'Password has been reset successfully.';
    public const RESET_LINK_SENT = 'Reset link has been sent to your email.';
    public const INVALID_TOKEN = 'Invalid or expired token.';

    // Device messages
    public const DEVICE_CREATED = 'Device created successfully.';
    public const DEVICE_UPDATED = 'Device updated successfully.';
    public const DEVICE_DELETED = 'Device deleted successfully.';
    public const DEVICE_GET_SUCCESS = 'Device fetched successfully.';
    public const DEVICE_NOT_FOUND = 'Device not found.';

    // Device Link message
    public const DEVICE_LINK_CREATED = 'Device link created successfully.';
    public const DEVICE_LINK_UPDATED = 'Device link updated successfully.';
    public const DEVICE_LINK_DELETED = 'Device link deleted successfully.';
    public const DEVICE_LINK_GET_SUCCESS = 'Device link fetched successfully.';
    public const DEVICE_LINK_NOT_FOUND = 'Device link not found.';

    // DataLog messages
    public const DATA_LOG_GET_SUCCESS = 'Data log fetched successfully.';

    // Registration messages
    public const REGISTRATION_SUCCESS = 'Registration successful';
    public const REGISTRATION_FAILED = 'Registration failed';

}

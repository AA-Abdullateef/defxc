<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company / Branding
    |--------------------------------------------------------------------------
    */
    'company_username'  => env('COMPANY_USERNAME', 'defxc'),
    'company_location'  => env('COMPANY_LOCATION', 'United States'),
    'company_url'       => env('COMPANY_URL', 'defxc.com'),
    'company_full_name' => env('COMPANY_FULL_NAME', 'DEFXC Financial Platform'),
    'company_short'     => env('COMPANY_SHORT', 'DEFXC'),


    /*
    |--------------------------------------------------------------------------
    | OTP / Token Settings
    |--------------------------------------------------------------------------
    */
    'otp_digits'                 => 6,
    'otp_registration_ttl'       => 10,   // minutes
    'otp_password_reset_ttl'     => 10,   // minutes
    'reset_token_ttl'            => 15,   // minutes
    'two_factor_token_ttl'       => 10,   // minutes
    'two_factor_token_digits'    => 5,
    'otp_max_attempts'           => 5,

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    'max_profile_photo_kb'  => 1024,   // ~1000KB
    'max_deposit_proof_kb'  => 1024,

];
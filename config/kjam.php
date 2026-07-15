<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KJA Application Settings
    |--------------------------------------------------------------------------
    |
    | Application metadata and project-level settings.
    | Keep values simple and backward compatible.
    |
    */

    'name' => env('KJAM_NAME', 'KJA Event Manager'),
    'short_name' => env('KJAM_SHORT_NAME', 'KJAM'),
    'mvp_name' => env('KJAM_MVP_NAME', 'CAI Operational'),
    'default_timezone' => env('KJAM_TIMEZONE', 'Asia/Jakarta'),
    'support_email' => env('KJAM_SUPPORT_EMAIL', 'support@example.com'),
];

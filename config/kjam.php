<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KJA Application Settings
    |--------------------------------------------------------------------------
    |
    | Placeholder configuration for project-level settings.
    | Keep values here simple and backward compatible.
    | Avoid moving business rules into config unless the rule is truly global.
    |
    */

    'name' => env('KJAM_NAME', 'KJA Event Manager'),
    'short_name' => env('KJAM_SHORT_NAME', 'KJAM'),
    'mvp_name' => env('KJAM_MVP_NAME', 'CAI Operational'),
    'default_timezone' => env('KJAM_TIMEZONE', 'Asia/Jakarta'),
    'support_email' => env('KJAM_SUPPORT_EMAIL', 'support@example.com'),
];

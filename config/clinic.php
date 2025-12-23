<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Appointment Settings
    |--------------------------------------------------------------------------
    */
    'appointments' => [
        'slot_duration' => env('CLINIC_SLOT_DURATION', 30),
        'max_advance_days' => env('CLINIC_MAX_ADVANCE_DAYS', 30),
        'min_advance_hours' => env('CLINIC_MIN_ADVANCE_HOURS', 2),
        'max_per_day' => env('CLINIC_MAX_APPOINTMENTS_PER_DAY', 20),
        'cancellation_hours' => env('CLINIC_CANCELLATION_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'slots_ttl' => env('CLINIC_SLOTS_CACHE_TTL', 300), // 5 minutes
        'dashboard_ttl' => env('CLINIC_DASHBOARD_CACHE_TTL', 600), // 10 minutes
        'settings_ttl' => env('CLINIC_SETTINGS_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'default_amount' => env('CLINIC_DEFAULT_PAYMENT_AMOUNT', 200),
        'currency' => env('CLINIC_CURRENCY', 'EGP'),
        'allow_partial' => env('CLINIC_ALLOW_PARTIAL_PAYMENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'reminder_hours' => env('CLINIC_REMINDER_HOURS', 24),
        'sms_enabled' => env('CLINIC_SMS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Working Hours Defaults
    |--------------------------------------------------------------------------
    */
    'working_hours' => [
        'default_start' => env('CLINIC_DEFAULT_START_TIME', '09:00'),
        'default_end' => env('CLINIC_DEFAULT_END_TIME', '17:00'),
        'break_start' => env('CLINIC_DEFAULT_BREAK_START', '13:00'),
        'break_end' => env('CLINIC_DEFAULT_BREAK_END', '14:00'),
    ],
];

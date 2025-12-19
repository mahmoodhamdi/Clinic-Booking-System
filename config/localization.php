<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when no locale is specified in the request.
    |
    */
    'default' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale to use when a translation is not found.
    |
    */
    'fallback' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of all supported locales in the application.
    | Each locale has its name, native name, direction, and flag.
    |
    */
    'supported' => [
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'direction' => 'rtl',
            'flag' => '🇸🇦',
            'date_format' => 'd/m/Y',
            'time_format' => 'h:i A',
            'datetime_format' => 'd/m/Y h:i A',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'direction' => 'ltr',
            'flag' => '🇺🇸',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'datetime_format' => 'Y-m-d H:i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Locales
    |--------------------------------------------------------------------------
    |
    | List of RTL (Right-to-Left) locales.
    |
    */
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],

    /*
    |--------------------------------------------------------------------------
    | API Header
    |--------------------------------------------------------------------------
    |
    | The header name to use for locale detection in API requests.
    |
    */
    'api_header' => 'Accept-Language',

    /*
    |--------------------------------------------------------------------------
    | Query Parameter
    |--------------------------------------------------------------------------
    |
    | The query parameter name to use for locale detection.
    |
    */
    'query_param' => 'lang',
];

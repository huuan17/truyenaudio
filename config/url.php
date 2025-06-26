<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains URL configuration for different environments
    | to ensure proper URL generation across development, staging, and production.
    |
    */

    'environments' => [
        'local' => [
            'url' => env('APP_URL', 'http://localhost:8000'),
            'force_https' => false,
        ],
        'staging' => [
            'url' => env('APP_URL', 'https://staging.yourdomain.com'),
            'force_https' => true,
        ],
        'production' => [
            'url' => env('APP_URL', 'https://yourdomain.com'),
            'force_https' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure asset URLs for different environments
    |
    */
    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CDN URLs for static assets in production
    |
    */
    'cdn_url' => env('CDN_URL'),
];

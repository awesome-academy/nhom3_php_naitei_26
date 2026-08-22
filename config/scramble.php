<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * API path. By default, all routes starting with this path will be added to the docs.
     */
    'api_path' => 'api/v1',

    'api_domain' => null,

    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Public Service Management System REST API Documentation',
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        'title' => 'Public Service Management System - API Documentation',
        'theme' => 'light',
        'hide_try_it' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
    ],

    /*
     * The list of middleware of the documentation page.
     */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];

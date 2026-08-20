<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | These domains will receive stateful authentication cookies when using
    | Sanctum for SPA authentication. For API token usage, this still
    | should be correctly configured to avoid auth conflicts.
    |
    */

    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,127.0.0.1,localhost:3000,127.0.0.1:8000,::1'
    )),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | These guards are checked when Sanctum tries to authenticate a request.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
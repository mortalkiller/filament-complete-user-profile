<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | Leave this null to use the model configured by the active Laravel
    | authentication provider. Set it explicitly only when your application
    | authenticates a different model in the Filament panel.
    |
    */
    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Profile storage
    |--------------------------------------------------------------------------
    |
    | "user" stores package-managed fields directly on the authenticatable
    | model. "separate" stores them in a package-owned profile table.
    |
    */
    'storage' => 'user',

    /*
    |--------------------------------------------------------------------------
    | Column names
    |--------------------------------------------------------------------------
    |
    | Change these when your application already has equivalent columns and
    | you want the package to reuse them instead of creating duplicates.
    |
    */
    'columns' => [
        'avatar' => 'avatar_url',
        'locale' => 'locale',

        'mfa' => [
            'secret' => 'app_authentication_secret',
            'recovery_codes' => 'app_authentication_recovery_codes',
            'email_enabled' => 'has_email_authentication',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Separate profile table
    |--------------------------------------------------------------------------
    */
    'profile_table' => 'filament_user_profiles',
];

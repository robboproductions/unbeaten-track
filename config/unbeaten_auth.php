<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User roles (users.role)
    |--------------------------------------------------------------------------
    |
    | Stored as snake_case keys. Add more later (e.g. content_creator) and
    | extend admin_panel_roles or split routes by middleware.
    |
    */

    'roles' => [
        'super_admin' => 'Super admin',
        'admin' => 'Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Who may use the /admin area
    |--------------------------------------------------------------------------
    */

    'admin_panel_roles' => ['super_admin', 'admin'],

    /*
    |--------------------------------------------------------------------------
    | Optional bootstrap users (db:seed)
    |--------------------------------------------------------------------------
    |
    | Set emails and passwords in .env — never commit real passwords.
    | Rows are created or updated by email (password is re-hashed each seed
    | when you set the env var).
    |
    */

    'bootstrap_users' => [
        [
            'role' => 'super_admin',
            'email' => env('AUTH_BOOTSTRAP_SUPER_EMAIL'),
            'name' => env('AUTH_BOOTSTRAP_SUPER_NAME', 'Super admin'),
            'password' => env('AUTH_BOOTSTRAP_SUPER_PASSWORD'),
        ],
        [
            'role' => 'admin',
            'email' => env('AUTH_BOOTSTRAP_ADMIN_EMAIL'),
            'name' => env('AUTH_BOOTSTRAP_ADMIN_NAME', 'Admin'),
            'password' => env('AUTH_BOOTSTRAP_ADMIN_PASSWORD'),
        ],
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform admin seed
    |--------------------------------------------------------------------------
    |
    | Credentials used by AdminSeeder to provision the platform admin. Kept in
    | config (read via config()) so they work under `config:cache` and never
    | live hardcoded in the repo. Set the values in the (gitignored) .env.
    |
    */

    'seed' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];

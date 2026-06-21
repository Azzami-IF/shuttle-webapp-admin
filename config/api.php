<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External API
    |--------------------------------------------------------------------------
    |
    | Base URL for the remote API used by the admin panel. Read from
    | environment variable `API_URL` (e.g. https://api.ambatu.my.id/api).
    |
    */
    'base_url' => env('API_URL', 'http://localhost:8000/api'),
];

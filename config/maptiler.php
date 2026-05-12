<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MapTiler (admin map preview — keys stay server-side)
    |--------------------------------------------------------------------------
    */

    'api_key' => env('MAPTILER_API_KEY'),

    'map_style' => env('MAPTILER_MAP_STYLE', 'streets-v2'),

    'http_referer' => env('MAPTILER_HTTP_REFERER', env('APP_URL', 'http://localhost')),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Town content verification (admin)
    |--------------------------------------------------------------------------
    |
    | Keys are stored on towns.verification_status. Changing the selection
    | updates verified_at / verified_by on save.
    |
    */

    'statuses' => [
        'unverified' => 'Not verified',
        'partially_verified' => 'Partially verified',
        'verified' => 'Verified',
    ],

];

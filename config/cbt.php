<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Python IRT Microservice Config
    |--------------------------------------------------------------------------
    |
    | Konfigurasi URL dan Secret Key untuk menghubungkan Laravel CBT dengan
    | Python IRT Microservice yang memproses MMLE/EM Item Response Theory secara asinkron.
    |
    */

    'irt_microservice_url' => env('IRT_MICROSERVICE_URL', 'http://127.0.0.1:8085/api/v1/irt/estimate'),

    'internal_secret' => env('CBT_INTERNAL_SECRET', 'cbt-secret-key-portal-lite'),

    'min_participants_irt' => (int) env('CBT_IRT_MIN_PARTICIPANTS', 100),
];

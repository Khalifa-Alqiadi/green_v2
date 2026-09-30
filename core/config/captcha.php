<?php

return [
    'secret' => env('NOCAPTCHA_SECRET'),
    'sitekey' => env('NOCAPTCHA_SITEKEY'),
    'version' => env('NOCAPTCHA_VERSION', 'v2'),
    'v3_score' => env('NOCAPTCHA_V3_SCORE', 0.5),
    'options' => [
        'timeout' => 30,
    ],
];

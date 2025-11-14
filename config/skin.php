<?php

return [
    // 'simple' = SimpleSkinAnalyzerService
    // 'api'    = SkinAnalyzerAPI (gọi Gemini trực tiếp)
    'driver' => env('SKIN_DRIVER', 'simple'),

    'gemini' => [
        'key'    => env('GEMINI_API_KEY'),
        'base'   => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com'),
        'model'  => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => 30,
    ],
];

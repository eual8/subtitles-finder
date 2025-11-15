<?php

return [
    'nodes' => [
        [
            'host' => env('TYPESENSE_HOST', 'localhost'),
            'port' => (int) env('TYPESENSE_PORT', 8108),
            'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
        ],
    ],
    'api_key' => env('TYPESENSE_API_KEY', ''),
    'connection_timeout_seconds' => (int) env('TYPESENSE_TIMEOUT', 2),
];

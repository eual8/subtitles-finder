<?php

declare(strict_types=1);

return [
    'default' => env('ELASTIC_CONNECTION', 'default'),
    'connections' => [
        'default' => [
            'hosts' => [
                env('ELASTIC_HOST', '127.0.0.1').':'.env('ELASTIC_PORT', 9200),
            ],
            'basicAuthentication' => [
                env('ELASTIC_USER'),
                env('ELASTIC_PASSWORD'),
            ],
        ],
    ],
];

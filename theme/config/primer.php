<?php

return [
    'routes' => [
        'prefix' => 'primer',
    ],

    'patterns' => [
        'driver' => 'file',

        'file' => [
            'paths' => [
                __DIR__ . '/../resources/patterns',
            ],
        ],
    ],

    'templates' => [
        'driver' => 'file',

        'file' => [
            'paths' => [
                __DIR__ . '/../resources/templates',
            ],
        ],
    ],

    'documents' => [
        'driver' => 'file',

        'file' => [
            'paths' => [
                __DIR__ . '/../resources/docs',
            ],
        ],
    ],
];

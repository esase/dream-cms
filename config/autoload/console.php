<?php

return [
    'profiler' => false,
    'php_settings' => [
        'display_startup_errors' => true,
        'display_errors' => true
    ], 
    'static_cache' => [
        'writable' => false,
        'readable' => false
    ],
    'dynamic_cache' => [
        'writable' => true,
        'readable' => true
    ],
    'view_manager' => [
        'display_not_found_reason' => false,
        'display_exceptions' => false
    ],
    'db' => [
        'profiler' => false
    ]
];
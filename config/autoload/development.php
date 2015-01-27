<?php

return [
    'profiler' => true,
    'php_settings' => [
        'display_startup_errors' => true,
        'display_errors' => true
    ], 
    'static_cache' => [
        'writable' => true,
        'readable' => true
    ],
    'dynamic_cache' => [
        'writable' => true,
        'readable' => true
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true
    ],
    'db' => [
        'profiler' => true
    ],
    'session' => [
        'validators' => null
    ],
];
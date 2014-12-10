<?php

return [
    'profiler' => true,
    'php_settings' => [
        'display_startup_errors' => true,
        'display_errors' => true
    ], 
    'static_cache' => [
        'writable' => false,
        'readable' => false
    ],
    'dynamic_cache' => [
        'writable' => false,
        'readable' => false
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
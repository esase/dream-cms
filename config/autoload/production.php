<?php

return [
    'profiler' => false,
    'php_settings' => [
        'display_startup_errors' => false,
        'display_errors' => false,
        'memory_limit' => '64M',
        'max_execution_time' => '120'
    ], 
    'view_manager' => [
        'display_not_found_reason' => false,
        'display_exceptions' => false
    ],
    'db' => [
        'profiler' => false
    ]
];
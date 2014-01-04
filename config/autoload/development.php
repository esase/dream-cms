<?php

return array(
    'profiler' => true,
    'php_settings' => array(
        'display_startup_errors' => true,
        'display_errors' => true
    ), 
    'static_cache' => array(
        'writable' => false,
        'readable' => false
    ),
    'dynamic_cache' => array(
        'writable' => false,
        'readable' => false
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true
    ),
    'db' => array(
        'profiler' => true
    )
);
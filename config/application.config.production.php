<?php

return [
    'module_listener_options' => [
        'config_cache_enabled' => !isset($isConfigDirWritable) || true === $isConfigDirWritable,
        'config_cache_key' => APPLICATION_ENV,
        'module_map_cache_enabled' => !isset($isConfigDirWritable) || true === $isConfigDirWritable,
        'module_map_cache_key' => APPLICATION_ENV,
        'cache_dir' => $applicationConfigCache
    ]
];
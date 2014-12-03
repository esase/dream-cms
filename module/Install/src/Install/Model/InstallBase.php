<?php

namespace Install\Model;

class InstallBase
{
    /**
     * Writable resources
     * @var array
     */
    protected $writableResources = [
        'data/cache/application',
        'data/cache/config',
        'data/session',
        'config/module/custom.php',
        'config/module/system.php',
        'public/resource',
        'public/resource/filemanager',
        'public/resource/user',
        'public/resource/user/thumbnail',
        'public/layout_cache/css',
        'public/layout_cache/js',
        'public/layout/base',
        'public/layout',
        'public/captcha',
        'data/log',
    ];

    /**
     * Get not writable resources
     *
     * @return array
     */
    public function getNotWritableResources()
    {
        sort($this->writableResources);
        $resources = [];

        foreach ($this->writableResources as $path) {
            if (false === ($result = is_writable(APPLICATION_ROOT . '/' . $path))) {
                $resources[] = [
                    'path' => $path,
                    'current' => 'Not writable',
                    'desired' => 'Writable'
                ];
            }
        }

        return $resources ? $resources  : [];
    }
}
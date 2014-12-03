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
     * Php extensions
     * @var array
     */
    protected $phpExtensions = [
        'intl',
        'zlib',
        'gd',
        'fileinfo',
        'zip',
        'pdo_mysql',
        'curl',
        'mbstring'
    ];

    /**
     * Php settings
     * @var array
     */
    protected $phpSettings = [
        'allow_url_fopen' => 1,
        'file_uploads' => 1
    ];

    /**
     * Php version 
     * @var string
     */
    protected $phpVersion = '5.4.0';

    /**
     * Get not configured php settings
     *
     * @return array
     */
    public function getNotConfiguredPhpSettings()
    {
        asort($this->phpSettings);
        $settings = [];

        foreach ($this->phpSettings as $setting => $value) {
            if ($value != ($currentSettingValue = ini_get($setting))) {
                $settings[] = [
                    'name' => $setting,
                    'current' => $currentSettingValue,
                    'desired' => $value
                ];
            }
        }

        // check the PHP version
        if (version_compare(PHP_VERSION, $this->phpVersion) == -1) {
            $settings[] = [
                'name' => 'PHP',
                'current' => PHP_VERSION,
                'desired' => $this->phpVersion
            ];
        }

        return $settings ? $settings  : [];
    }

    /**
     * Get not installed php extensions
     *
     * @return array
     */
    public function getNotInstalledPhpExtensions()
    {
        asort($this->phpExtensions);
        $extensions = [];

        foreach ($this->phpExtensions as $extension) {
            if (false === ($result = extension_loaded($extension))) {
                $extensions[] = [
                    'extension' => $extension,
                    'current' => 'Not installed',
                    'desired' => 'Installed'
                ];
            }
        }

        return $extensions ? $extensions  : [];
    }

    /**
     * Get not writable resources
     *
     * @return array
     */
    public function getNotWritableResources()
    {
        asort($this->writableResources);
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
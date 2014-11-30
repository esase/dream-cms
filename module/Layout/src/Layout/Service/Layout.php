<?php
namespace Layout\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class Layout
{
    /**
     * Current site layouts
     * @var array
     */
    protected static $currentLayouts;

    /**
     * Get layout path
     *
     * @return string
     */
    public static function getLayoutPath()
    {
        return APPLICATION_PUBLIC . '/' .
                ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout'] . '/';
    }

    /**
     * Get layout dir
     *
     * @return string
     */
    public static function getLayoutDir()
    {
        return ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout'];
    }

    /**
     * Get layout cache path
     *
     * @param string $type
     * @return string
     */
    public static function getLayoutCachePath($type = 'css')
    {
        return APPLICATION_PUBLIC . '/' . ($type == 'css'
                ? ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout_cache_css']
                : ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout_cache_js']) . '/';
    }

    /**
     * Get layout cache dir
     *
     * @param string $type
     * @return string
     */
    public static function getLayoutCacheDir($type = 'css')
    {
        return ($type == 'css'
                ? ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout_cache_css']
                : ServiceLocatorService::getServiceLocator()->get('Config')['paths']['layout_cache_js']);
    }

    /**
     * Set current layouts
     *
     * @param array $layouts
     * @return void
     */
    public static function setCurrentLayouts(array $layouts)
    {
        self::$currentLayouts = $layouts;
    }

    /**
     * Get current layouts
     *
     * @return array
     */
    public static function getCurrentLayouts()
    {
        return self::$currentLayouts;
    }
}
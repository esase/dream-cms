<?php
namespace Application\Service;

class Application
{
    /**
     * Get application url
     *
     * @return array
     */
    public static function getApplicationUrl()
    {
        return ServiceManagers::getServiceManager()->get('Request')->getBaseUrl();
    }

    /**
     * Get config path
     *
     * @return string
     */
    public static function getConfigCachePath()
    {
        return APPLICATION_ROOT .
                '/' . ServiceManagers::getServiceManager()->get('Config')['paths']['config_cache'];
    }

    /**
     * Get resources dir
     *
     * @return string
     */
    public static function getResourcesDir()
    {
        return APPLICATION_PUBLIC . '/' .
                ServiceManagers::getServiceManager()->get('Config')['paths']['resource'] . '/';
    }

    /**
     * Get resources url
     *
     * @return string
     */
    public static function getResourcesUrl()
    {
        return self::getApplicationUrl() . '/' .
                ServiceManagers::getServiceManager()->get('Config')['paths']['resource'] . '/';
    }
}
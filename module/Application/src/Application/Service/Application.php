<?php
namespace Application\Service;

class Application
{
    /**
     * Get config path
     *
     * @return string
     */
    public static function getConfigCachePath()
    {
        return APPLICATION_ROOT .
                '/' . ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['config_cache'];
    }

    /**
     * Get resources dir
     *
     * @return string
     */
    public static function getResourcesDir()
    {
        return APPLICATION_PUBLIC . '/' .
                ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['resource'] . '/';
    }

    /**
     * Get resources url
     *
     * @return string
     */
    public static function getResourcesUrl()
    {
        return ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['resource'] . '/';
    }
}
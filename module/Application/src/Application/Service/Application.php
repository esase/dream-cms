<?php
namespace Application\Service;

class Application
{
    /**
     * Get base layout path
     *
     * @return string
     */
    public static function getBaseLayoutPath($full = true)
    {
        $basePath = ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['layout_base'];

        return $full
            ? APPLICATION_PUBLIC . '/' . $basePath
            : $basePath;
    }

    /**
     * Get tmp path
     *
     * @return string
     */
    public static function getTmpPath()
    {
        return APPLICATION_ROOT .
                '/' . ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['tmp'];
    }

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
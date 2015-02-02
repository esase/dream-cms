<?php
namespace Application\Service;

class Application
{
    /**
     * Get module path
     *
     * @return string
     */
    public static function getModulePath($full = true)
    {
        $basePath = ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['module'];

        return $full
            ? APPLICATION_ROOT .  '/' . $basePath
            : $basePath;
    }

    /**
     * Get module view dir
     *
     * @return string
     */
    public static function getModuleViewDir()
    {
        return ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['module_view'];
    }

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
     * Get layout path
     *
     * @return string
     */
    public static function getLayoutPath($full = true)
    {
        $layoutPath = ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['layout'];

        return $full
            ? APPLICATION_PUBLIC . '/' . $layoutPath
            : $layoutPath;
    }

    /**
     * Get custom module config
     *
     * @return string
     */
    public static function getCustomModuleConfig()
    {
        return APPLICATION_ROOT . '/' .
                ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['custom_module_config'];
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
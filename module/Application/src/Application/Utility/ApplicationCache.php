<?php
namespace Application\Utility;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\Application as ApplicationService;
use Layout\Service\Layout as LayoutService;
use Application\Model\ApplicationAdminMenu as AdminMenuBaseModel;
use Application\Model\ApplicationTimeZone as TimeZoneBaseModel;
use Application\Model\ApplicationSetting as SettingBaseModel;
use Application\Model\ApplicationBase as ApplicationBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class ApplicationCache
{
    /**
     * Clear module cache
     *
     * @return boolean
     */
    public static function clearModuleCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([ApplicationBaseModel::CACHE_MODULES_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear setting cache
     *
     * @return boolean
     */
    public static function clearSettingCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([SettingBaseModel::CACHE_SETTINGS_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear time zone cache
     *
     * @return boolean
     */
    public static function clearTimeZoneCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([TimeZoneBaseModel::CACHE_TIME_ZONES_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear admin menu cache
     *
     * @return boolean
     */
    public static function clearAdminMenuCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([AdminMenuBaseModel::CACHE_ADMIN_MENU_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear static cache
     *
     * @return boolean
     */
    public static function clearStaticCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->get('Application\Cache\Static')->flush();
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear dynamic cache
     *
     * @return boolean
     */
    public static function clearDynamicCache()
    {
        if (null == ($dynamicCache =
                ApplicationSettingService::getSetting('application_dynamic_cache'))) {

            return true;
        }

        try {
            return ServiceLocatorService::getServiceLocator()->get('Application\Cache\Dynamic')->flush();
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear config cache
     *
     * @return boolean
     */
    public static function clearConfigCache()
    {
        try {
            return ApplicationFileSystem::deleteFiles(ApplicationService::getConfigCachePath());
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear js cache
     *
     * @return boolean
     */
    public static function clearJsCache()
    {
        try {
            return ApplicationFileSystem::deleteFiles(LayoutService::getLayoutCachePath('js'));
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Clear css cache
     *
     * @return boolean
     */
    public static function clearCssCache()
    {
        try {
            return ApplicationFileSystem::deleteFiles(LayoutService::getLayoutCachePath());
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }

    /**
     * Get cache name
     * 
     * @param string $name
     * @param array $argsList
     * @return string
     */
    public static function getCacheName($name, array $argsList = [])
    {
        return md5($name . self::processArgs($argsList));
    }

    /**
     * Process arguments
     *
     * @param mixed $args
     * @return string
     */
    private static function processArgs($args)
    {
        $result = null;

        if(!$args) {
            return;
        }

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $result .= self::processArgs($arg);
            }
            else if ( is_scalar($arg) ) {
                $result .= ':' . $arg;
            }
        }

        return $result;
    }
}
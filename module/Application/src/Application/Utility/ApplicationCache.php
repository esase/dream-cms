<?php
namespace Application\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Application\Service\Application as ApplicationService;
use Layout\Service\Layout as LayoutService;
use Application\Model\ApplicationAdminMenu as AdminMenuBaseModel;
use Application\Model\ApplicationTimeZone as TimeZoneBaseModel;
use Application\Model\ApplicationSetting as SettingBaseModel;

class ApplicationCache
{
    /**
     * Clear setting cache
     *
     * @return boolean
     */
    public static function clearSettingCache()
    {
        return ServiceManagerService::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([SettingBaseModel::CACHE_SETTINGS_DATA_TAG]);
    }

    /**
     * Clear time zone cache
     *
     * @return boolean
     */
    public static function clearTimeZoneCache()
    {
        return ServiceManagerService::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([TimeZoneBaseModel::CACHE_TIME_ZONES_DATA_TAG]);
    }

    /**
     * Clear admin menu cache
     *
     * @return boolean
     */
    public static function clearAdminMenuCache()
    {
        return ServiceManagerService::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([AdminMenuBaseModel::CACHE_ADMIN_MENU_DATA_TAG]);
    }

    /**
     * Clear static cache
     *
     * @return boolean
     */
    public static function clearStaticCache()
    {
        return ServiceManagerService::getServiceManager()->get('Application\Cache\Static')->flush();
    }

    /**
     * Clear dynamic cache
     *
     * @return boolean
     */
    public static function clearDynamicCache()
    {
        return ServiceManagerService::getServiceManager()->get('Application\Cache\Dynamic')->flush();
    }

    /**
     * Clear config cache
     *
     * @return boolean
     */
    public static function clearConfigCache()
    {
        return ApplicationFileSystem::deleteFiles(ApplicationService::getConfigCachePath());
    }

    /**
     * Clear js cache
     *
     * @return boolean
     */
    public static function clearJsCache()
    {
        return ApplicationFileSystem::deleteFiles(LayoutService::getLayoutCachePath('js'));
    }

    /**
     * Clear css cache
     *
     * @return boolean
     */
    public static function clearCssCache()
    {
        return ApplicationFileSystem::deleteFiles(LayoutService::getLayoutCachePath());
    }

    /**
     * Get cache name
     * 
     * @param string $name
     * @param array $argsList
     * @return string
     */
    public static function getCacheName($name, array $argsList = array())
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
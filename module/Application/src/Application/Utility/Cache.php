<?php

namespace Application\Utility;

use Application\Service\ServiceManager;
use Application\Service\Service as ApplicationService;
use Application\Model\AdminMenu as AdminMenuBaseModel;
use Application\Model\TimeZone as TimeZoneBaseModel;
use Application\Model\Setting as SettingBaseModel;

class Cache
{
    /**
     * Clear setting cache
     *
     * @return boolean
     */
    public static function clearSettingCache()
    {
        return ServiceManager::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([SettingBaseModel::CACHE_SETTINGS_DATA_TAG]);
    }

    /**
     * Clear time zone cache
     *
     * @return boolean
     */
    public static function clearTimeZoneCache()
    {
        return ServiceManager::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([TimeZoneBaseModel::CACHE_TIME_ZONES_DATA_TAG]);
    }

    /**
     * Clear admin menu cache
     *
     * @return boolean
     */
    public static function clearAdminMenuCache()
    {
        return ServiceManager::getServiceManager()->
            get('Application\Cache\Static')->clearByTags([AdminMenuBaseModel::CACHE_ADMIN_MENU_DATA_TAG]);
    }

    /**
     * Clear static cache
     *
     * @return boolean
     */
    public static function clearStaticCache()
    {
        return ServiceManager::getServiceManager()->get('Application\Cache\Static')->flush();
    }

    /**
     * Clear dynamic cache
     *
     * @return boolean
     */
    public static function clearDynamicCache()
    {
        return ServiceManager::getServiceManager()->get('Application\Cache\Dynamic')->flush();
    }

    /**
     * Clear config cache
     *
     * @return boolean
     */
    public static function clearConfigCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getConfigCachePath());
    }

    /**
     * Clear js cache
     *
     * @return boolean
     */
    public static function clearJsCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getLayoutCachePath('js'));
    }

    /**
     * Clear css cache
     *
     * @return boolean
     */
    public static function clearCssCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getLayoutCachePath());
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
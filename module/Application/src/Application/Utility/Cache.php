<?php

namespace Application\Utility;

use Application\Service\Service as ApplicationService;
use Application\Utility\FileSystem;

class Cache
{
    /**
     * Clear static cache
     *
     * @return boolean
     */
    public static function clearStaticCache()
    {
        return ApplicationService::getServiceManager()->get('Cache\Static')->flush();
    }

    /**
     * Clear dynamic cache
     *
     * @return boolean
     */
    public static function clearDynamicCache()
    {
        return ApplicationService::getServiceManager()->get('Cache\Dynamic')->flush();
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
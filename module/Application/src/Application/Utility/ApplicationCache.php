<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Utility;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\Application as ApplicationService;
use Application\Model\ApplicationAdminMenu as AdminMenuBaseModel;
use Application\Model\ApplicationTimeZone as TimeZoneBaseModel;
use Application\Model\ApplicationSetting as SettingBaseModel;
use Application\Model\ApplicationBase as ApplicationBaseModel;
use Layout\Service\Layout as LayoutService;
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
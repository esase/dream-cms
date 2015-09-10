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
namespace Layout\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class Layout
{
    /**
     * Current site layouts
     *
     * @var array
     */
    protected static $currentLayouts;

    /**
     * Get custom layouts
     *
     * @param boolean $onlyCustom
     * @return array
     */
    public static function getLayouts($onlyCustom = true)
    {
        return ServiceLocatorService::getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Layout\Model\LayoutBase')
            ->getAllInstalledLayouts($onlyCustom);
    }

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
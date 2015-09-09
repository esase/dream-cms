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
namespace Page\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class Page
{
    /**
     * Page layouts
     *
     * @var array
     */
    protected static $pageLayouts = null;

    /**
     * Widget layouts
     *
     * @var array
     */
    protected static $widgetLayouts = null;

    /**
     * Current page
     *
     * @var array
     */
    protected static $currentPage = [];

    /**
     * Get page layouts
     *
     * @return array
     */
    public static function getPageLayouts()
    {
        if (null === self::$pageLayouts) {
            self::$pageLayouts = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase')
                ->getPageLayouts();
        }

        return self::$pageLayouts;
    }

    /**
     * Get widget layouts
     *
     * @return array
     */
    public static function getWidgetLayouts()
    {
        if (null === self::$widgetLayouts) {
            self::$widgetLayouts = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageWidgetSetting')
                ->getWidgetLayouts();
        }

        return self::$widgetLayouts;
    }

    /**
     * Set current page
     *
     * @param array $page
     * @return void
     */
    public static function setCurrentPage(array $page)
    {
        self::$currentPage = $page;
    }

    /**
     * Get current page
     *
     * @return array
     */
    public static function getCurrentPage()
    {
        return self::$currentPage;
    }
}
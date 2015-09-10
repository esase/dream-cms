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
namespace Page\View\Widget;

use Localization\Service\Localization as LocalizationService;
use Page\Utility\PageProvider as PageProviderUtility;
use Page\Service\Page as PageService;

class PageSidebarMenuWidget extends PageAbstractWidget
{
    /**
     * Model instance
     *
     * @var \Page\Model\PageBase
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Page\Model\PageBase
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase');
        }

        return $this->model;
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (null != ($currentPage = PageService::getCurrentPage())) {
            $menuType = $this->getWidgetSetting('page_sidebar_menu_type');
            $showDynamicPages = (int) $this->getWidgetSetting('page_sidebar_menu_show_dynamic');
            $currentLanguage = LocalizationService::getCurrentLocalization()['language'];
            $pages = [];

            // collect sidebar menu items
            foreach ($this->getModel()->getPagesMap($currentLanguage) as $page) {
                // check the type of menu
                if ($page['parent'] != ($menuType ==
                        'sidebar_menu_subpages' ? $currentPage['slug'] : $currentPage['parent_slug'])) {

                    continue;
                }

                // get dynamic pages
                if (!empty($page['pages_provider'])) {
                    if ($showDynamicPages) {
                        if (null != ($dynamicPages =
                                PageProviderUtility::getPages($page['pages_provider'], $currentLanguage))) {

                            // process only the first pages level
                            foreach ($dynamicPages as $dynamicPage) {
                                // check received params
                                if (!isset($dynamicPage['url_params'], $dynamicPage['url_title'])) {
                                    continue;
                                }

                                if (false !== ($pageUrl = $this->
                                        getView()->pageUrl($page['slug'], [], $currentLanguage, true))) {

                                    $pages[] = [
                                        'active' => !empty($dynamicPage['url_active']),
                                        'url' => $pageUrl,
                                        'title' => $dynamicPage['url_title'],
                                        'params' => $dynamicPage['url_params']
                                    ];
                                }
                            }
                        }
                    }
                }
                else {
                    // get a page url
                    if (false === ($pageUrl = $this->getView()->pageUrl($page['slug']))) {
                        continue;
                    }

                    $pages[] = [
                        'active' => $currentPage['slug'] == $page['slug'],
                        'url' => $pageUrl,
                        'title' => $this->getView()->pageTitle($page)
                    ];
                }
            }

            if ($pages) {
                return $this->getView()->partial('page/widget/sidebar-menu', [
                    'pages' => $pages
                ]);
            }
        }

        return false;
    }
}
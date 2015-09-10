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
namespace Page\View\Helper;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\Utility\PageProvider as PageProviderUtility;
use Page\Model\PageNestedSet;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation;

class PageXmlSiteMap extends AbstractHelper
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
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase');
        }

        return $this->model;
    }

    /**
     * Page xml site map
     * 
     * @return \Zend\Navigation\Navigation
     */
    public function __invoke()
    {
        $xmlMap = [];

        if (null != ($siteMap = $this->getModel()->getAllPagesMap())) {
            foreach($siteMap as $language => $pages) {
                foreach($pages as $pageOptions) {
                    if ($pageOptions['xml_map'] == PageNestedSet::PAGE_IN_XML_MAP) {
                        // get dynamic pages
                        if (!empty($pageOptions['pages_provider'])) {
                            if (null != ($dynamicPages =
                                    PageProviderUtility::getPages($pageOptions['pages_provider'], $language))) {

                                $xmlMap = array_merge($xmlMap,
                                        $this->processDynamicPages($pageOptions, $dynamicPages, $language));
                            }
                        }
                        else {
                            // get a page url
                            if (false !== ($pageUrl = $this->getView()->pageUrl($pageOptions['slug'], [], $language))) {
                                $xmlMap[] = [
                                    'uri' => $this->getView()->
                                            url('page', ['language' => $language, 'page_name' => $pageUrl], ['force_canonical' => true]),

                                    'lastmod' => $pageOptions['date_edited'],
                                    'changefreq' => $pageOptions['xml_map_update'],
                                    'priority' => $pageOptions['xml_map_priority']
                                ];
                            }
                        }
                    }
                }
            }
        }

        return new Navigation($xmlMap);
    }

    /**
     * Process dynamic pages
     *
     * @param array $pageOptions
     * @param array $dynamicPages
     * @param string $language
     * @return array
     */
    protected function processDynamicPages(array $pageOptions, array $dynamicPages, $language)
    {
        $xmlMap = [];

        foreach ($dynamicPages as $dynamicPage) {
            // check received params
            if (!isset($dynamicPage['url_params'], $dynamicPage['xml_map'])) {
                continue;
            }

            if (false !== ($pageUrl = $this->getView()->pageUrl($pageOptions['slug'], [], $language, true))) {
                $pageUrl = $this->getView()->url('page', ['language' =>
                        $language, 'page_name' => $pageUrl] + $dynamicPage['url_params'], ['force_canonical' => true]);

                $lastMod = empty($dynamicPage['xml_map']['lastmod'])
                    ? $pageOptions['date_edited']
                    : $dynamicPage['xml_map']['lastmod'];

                $changeFreq = empty($dynamicPage['xml_map']['changefreq'])
                    ? $pageOptions['xml_map_update']
                    : $dynamicPage['xml_map']['changefreq'];

                $priority = empty($dynamicPage['xml_map']['priority'])
                    ? $pageOptions['xml_map_priority']
                    : $dynamicPage['xml_map']['priority'];

                $xmlMap[] = [
                    'uri' => $pageUrl,
                    'lastmod' => $lastMod,
                    'changefreq' => $changeFreq,
                    'priority' => $priority
                ];

                // check for children
                if (!empty($dynamicPage['children'])) {
                    $xmlMap = array_merge($xmlMap, $this->
                            processDynamicPages($pageOptions, $dynamicPage['children'], $language));
                }
            }
        }

        return $xmlMap;
    }
}
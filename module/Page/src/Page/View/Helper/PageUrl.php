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

use Application\Model\ApplicationAbstractBase as ApplicationAbstractBaseModel;
use Page\Model\PageNestedSet;
use Page\Utility\PagePrivacy as PagePrivacyUtility;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;
use Zend\View\Helper\AbstractHelper;

class PageUrl extends AbstractHelper
{
    /**
     * Pages map
     *
     * @var array
     */
    protected $pagesMap = [];

    /**
     * Home page
     *
     * @var string
     */
    protected $homePage;

    /**
     * Class constructor
     *
     * @param string $homePage
     * @param array $pagesMap
     */
    public function __construct($homePage, array $pagesMap = [])
    {
        $this->homePage = $homePage;
        $this->pagesMap = $pagesMap;
    }

    /**
     * Page url
     *
     * @param string slug
     * @param array $privacyOptions
     * @param string $language
     * @param boolean $trustedPrivacyData
     * @param string|integer $objectId
     * @return string|boolean
     */
    public function __invoke($slug = null, array $privacyOptions = [], $language = null, $trustedPrivacyData = false, $objectId = null)
    {
        if (!$slug) {
            $slug = $this->homePage;
        }

        if (!$language) {
            $language = LocalizationService::getCurrentLocalization()['language'];
        }

        $pageUrl = $this->getPageUrl($slug, $language, $privacyOptions, $trustedPrivacyData, $objectId);

        // compare the slug for the home page 
        if ($this->homePage == $slug && false !== $pageUrl) {
            $pageUrl = null;
        }

        return $pageUrl;
    }

    /**
     * Get page url
     *
     * @param string $slug
     * @param string $language
     * @param array $privacyOptions     
     * @param boolean $trustedPrivacyData
     * @param string $objectId
     * @return string|boolean
     */
    protected function getPageUrl($slug, $language, array $privacyOptions = [], $trustedPrivacyData = false, $objectId = null) 
    {
        if (!isset($this->pagesMap[$language])
                || !array_key_exists($slug, $this->pagesMap[$language])) {

            return false;
        }

        // get a page info
        $page = $this->pagesMap[$language][$slug];

        // check the page's status
        if ($page['active'] != PageNestedSet::PAGE_STATUS_ACTIVE
                || $page['module_status'] != ApplicationAbstractBaseModel::MODULE_STATUS_ACTIVE) {

            return false;
        }

        // check the page's privacy
        if (false == ($result = PagePrivacyUtility::
                checkPagePrivacy($page['privacy'], $privacyOptions, $trustedPrivacyData, $objectId))) {

            return false;
        }

        // check the page's visibility
        if (!empty($page['hidden']) && in_array(UserIdentityService::getCurrentUserIdentity()['role'], 
                $page['hidden'])) {

            return false;
        }

        // check for a parent and 
        if (!empty($page['parent'])) {
            if (false === ($parentUrl = $this->getPageUrl($page['parent'], $language, [], false))) {
                return false;
            }

            // build a link (skip the home page)
            if ($this->pagesMap[$language][$page['parent']]['level'] > 1) {
                $slug = $parentUrl . '/' . $slug;
            }
        }

        return $slug;
    }
}
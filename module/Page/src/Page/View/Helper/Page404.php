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

use Application\Utility\ApplicationDisableSite as DisableSiteUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Localization\Service\Localization as LocalizationService;
use User\Service\UserIdentity as UserIdentityService;
use Page\Event\PageEvent;
use Zend\View\Helper\AbstractHelper;
use Zend\Http\Response;

class Page404 extends AbstractHelper
{
    /**
     * Custom 404 page
     */
    const CUSTOM_404_PAGE = '404';

    /**
     * Model instance
     *
     * @var \Page\Model\PageNestedSet
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Page\Model\PageNestedSet
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()->get('Page\Model\PageNestedSet');
        }

        return $this->model;
    }

    /**
     * Page 404
     * 
     * @return string|boolean
     */
    public function __invoke()
    {
        $language = LocalizationService::getCurrentLocalization()['language'];
        $page404  = false;

        // get a custom 404 page's url
        if (true === DisableSiteUtility::isAllowedViewSite()
                && false !== ($page404 = $this->getView()->pageUrl(self::CUSTOM_404_PAGE, [], $language, true))) {

            $userRole = UserIdentityService::getCurrentUserIdentity()['role'];

            if (false == ($pageInfo = $this->
                    getModel()->getActivePageInfo(self::CUSTOM_404_PAGE, $userRole, $language))) {

                return false;
            }

            // fire the page show event
            PageEvent::firePageShowEvent($pageInfo['slug'], $language);

            // check for redirect
            if ($pageInfo['redirect_url']) {
                $response = ServiceLocatorService::getServiceLocator()->get('Response');
                $response->getHeaders()->addHeaderLine('Location', $pageInfo['redirect_url']);
                $response->setStatusCode(Response::STATUS_CODE_301);
                $response->sendHeaders();

                return false;
            }

            // get the page's breadcrumb
            $breadcrumb = $this->getModel()->
                    getActivePageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language);

            return $this->getView()->partial($this->getModel()->getLayoutPath() . $pageInfo['layout'], [
                'page' => $pageInfo,
                'breadcrumb' => $breadcrumb
            ]);
        }

        return $page404;
    }
}
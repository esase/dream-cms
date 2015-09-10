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
namespace User\Controller;

use Layout\Utility\LayoutCookie as LayoutCookieUtility;
use Layout\Service\Layout as LayoutService;
use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;

class UserAjaxController extends ApplicationAbstractBaseController
{
    /**
     * Model instance
     *
     * @var \User\Model\UserAjax
     */
    protected $model;

    /**
     * Get model
     *
     * @return \User\Model\UserAjax
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserAjax');
        }

        return $this->model;
    }

    /**
     * Logout 
     */
    public function ajaxLogoutAction()
    {
        $request  = $this->getRequest();

        if ($this->isGuest() || !$request->isPost()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // clear logged user's identity
        $user = UserIdentityService::getCurrentUserIdentity();
        UserIdentityService::getAuthService()->clearIdentity();
        $this->serviceLocator->get('Zend\Session\SessionManager')->rememberMe(0);

        // fire the user logout event
        UserEvent::fireLogoutEvent($user['user_id'], $user['nick_name']);

        return $this->getResponse();
    }

    /**
     * Select layout
     */
    public function ajaxSelectLayoutAction()
    {
        $request  = $this->getRequest();

        if ($request->isPost()) {
            if ((int) $this->applicationSetting('layout_select')) {
                $layoutId = $this->getSlug(-1);
                $layouts = LayoutService::getLayouts(false);

                // save selected layout
                if (array_key_exists($layoutId, $layouts)) {
                    if (!$this->isGuest()) {
                        $user = UserIdentityService::getCurrentUserIdentity();
                        $this->getModel()->selectLayout($layoutId, $user['user_id']);
                    }

                    LayoutCookieUtility::saveLayout($layoutId);
                }
            }
        }

        return $this->getResponse();
    }
}
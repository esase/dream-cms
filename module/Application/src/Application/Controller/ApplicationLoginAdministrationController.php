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
namespace Application\Controller;

use Acl\Service\Acl as AclService;
use User\Service\UserIdentity as UserIdentityService;
use User\Utility\UserAuthenticate as UserAuthenticateUtility;
use Zend\View\Model\ViewModel;
use Zend\Http\Response;

class ApplicationLoginAdministrationController extends ApplicationAbstractBaseController
{
    /**
     * Layout name
     *
     * @var string
     */
    protected $layout = 'layout/blank';

    /**
     * Admin menu model instance
     *
     * @var \Application\Model\ApplicationAdminMenu
     */
    protected $adminMenuModel;

    /**
     * Get admin menu model
     *
     * @return \Application\Model\ApplicationAdminMenu
     */
    protected function getAdminMenuModel()
    {
        if (!$this->adminMenuModel) {
            $this->adminMenuModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\ApplicationAdminMenu');
        }

        return $this->adminMenuModel;
    }

    /**
     * Index page
     */
    public function indexAction()
    {
        if (!UserIdentityService::isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $this->layout($this->layout);

        $loginForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\UserLogin');

        if ($this->getRequest()->isPost()) {
            // fill form with received values
            $loginForm->getForm()->setData($this->getRequest()->getPost());

            if ($loginForm->getForm()->isValid()) {
                $userName = $this->getRequest()->getPost('nickname');
                $password = $this->getRequest()->getPost('password');

                // check an authentication
                $authErrors = [];
                $result = UserAuthenticateUtility::
                        isAuthenticateDataValid($userName, $password, $authErrors);

                if (false === $result) {
                    $this->flashMessenger()->setNamespace('error');

                    // add auth error messages
                    foreach ($authErrors as $message) {
                        $this->flashMessenger()->addMessage($this->getTranslator()->translate($message));
                    }

                    return $this->reloadPage();
                }

                $rememberMe = null != ($remember = $this->getRequest()->getPost('remember')) 
                    ? true 
                    : false;

                // login a user
                UserAuthenticateUtility::loginUser($result['user_id'], $result['nick_name'], $rememberMe);

                // make a redirect
                if (null !== ($backUrl = $this->getRequest()->getQuery('back_url', null))) {
                    return $this->redirect()->toUrl($backUrl);
                }

                // search a first allowed admin page
                $adminMenu = $this->getAdminMenuModel()->getMenu();
                foreach ($adminMenu as $menuItems) {
                    foreach ($menuItems['items'] as $item) {
                        if (AclService::checkPermission($item['controller'] . ' ' . $item['action'], false)) {
                            return $this->redirectTo($item['controller'], $item['action']);
                        }
                    }
                }

                // redirect to the public home page
                $this->flashMessenger()->setNamespace('error');
                $this->flashMessenger()->addMessage($this->
                        getTranslator()->translate('There are no admin pages allowed for you!'));

                return $this->redirectTo('page', 'index', [], false, [], 'page');
            }
        }

        return new ViewModel([
            'login_form' => $loginForm->getForm()
        ]);
    }
}
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
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;
use User\Utility\UserAuthenticate as UserAuthenticateUtility;

class UserLoginWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (UserIdentityService::isGuest()) {
            // get a login form
            $loginForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\UserLogin');

            if ($this->getRequest()->isPost() &&
                    $this->getRequest()->getPost('form_name') == $loginForm->getFormName()) {

                // fill form with received values
                $loginForm->getForm()->setData($this->getRequest()->getPost());

                if ($loginForm->getForm()->isValid()) {
                    $userName = $this->getRequest()->getPost('nickname');
                    $password = $this->getRequest()->getPost('password');

                    // check an authentication
                    $authErrors = [];
                    $result = UserAuthenticateUtility::isAuthenticateDataValid($userName, $password, $authErrors);

                    if (false === $result) {
                        $this->getFlashMessenger()->setNamespace('error');

                        // add auth error messages
                        foreach ($authErrors as $message) {
                            $this->getFlashMessenger()->addMessage($this->translate($message));
                        }

                        return $this->reloadPage();
                    }

                    $rememberMe = null != ($remember = $this->getRequest()->getPost('remember')) 
                        ? true 
                        : false;

                    return $this->loginUser($result['user_id'], $result['nick_name'], $rememberMe);
                }
            }

            return $this->getView()->partial('user/widget/login', [
                'login_form' => $loginForm->getForm()
            ]);
        }

        return false;
    }
}
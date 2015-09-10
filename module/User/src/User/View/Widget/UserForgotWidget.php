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

use User\Model\UserWidget as UserWidgetModel;

class UserForgotWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get a forgot form
        $forgotForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\UserForgot')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost() &&
                $this->getRequest()->getPost('form_name') == $forgotForm->getFormName()) {

            // fill the form with received values
            $forgotForm->getForm()->setData($request->getPost(), false);

            if ($forgotForm->getForm()->isValid()) {
                $passwordResetUrl =  $this->getView()->pageUrl('user-password-reset', [], null, true); 

                if (false !== $passwordResetUrl) {
                    // get an user info
                    $userInfo = $this->getModel()->
                            getUserInfo($forgotForm->getForm()->getData()['email'], UserWidgetModel::USER_INFO_BY_EMAIL);

                    // generate a new activation code
                    if (true === ($result = $this->getModel()->generateActivationCode($userInfo))) {
                        $this->getFlashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->translate('We sent a message with a confirmation code. You should confirm the password reset'));
                    }
                    else {
                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));
                    }
                }
                else {
                    $this->getFlashMessenger()
                         ->setNamespace('error')
                         ->addMessage($this->translate('Password reset page is not allowed for you!'));
                }

                return $this->reloadPage();
            }
        }

        return $this->getView()->partial('user/widget/forgot', [
            'forgot_form' => $forgotForm->getForm()
        ]);
    }
}
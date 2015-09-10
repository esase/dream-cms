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

class UserActivateWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (null != ($userInfo = $this->getModel()->
                    getUserInfo($this->getSlug(), UserWidgetModel::USER_INFO_BY_SLUG))) {

            // get an activate form
            $activateForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\UserActivationCode')
                ->setModel($this->getModel())
                ->setUserId($userInfo['user_id']);

            $request = $this->getRequest();

            // validate the form
            if ($request->isPost() &&
                    $this->getRequest()->getPost('form_name') == $activateForm->getFormName()) {

                // fill the form with received values
                $activateForm->getForm()->setData($request->getPost(), false);

                // activate the users's status
                if ($activateForm->getForm()->isValid()) {
                    // approve the user
                    if (true !== ($approveResult = $this->getModel()->
                            setUserStatus($userInfo['user_id'], true, $userInfo, $userInfo['nick_name']))) {

                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));

                        return $this->reloadPage();
                    }

                    // login and redirect the user
                    return $this->loginUser($userInfo['user_id'], $userInfo['nick_name']);
                }
            }

            return $this->getView()->partial('user/widget/activate', [
                'activate_form' => $activateForm->getForm()
            ]);
        }

        return false;
    }
}
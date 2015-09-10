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

use Application\Service\ApplicationTimeZone as TimeZoneService;
use User\Service\UserIdentity as UserIdentityService;
use Acl\Model\AclBase as AclBaseModel;

class UserEditWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (!UserIdentityService::isGuest()) {
            // get an user form
            $userForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\User')
                ->setModel($this->getModel())
                ->setTimeZones(TimeZoneService::getTimeZones())
                ->setUserId(UserIdentityService::getCurrentUserIdentity()['user_id'])
                ->setUserAvatar(UserIdentityService::getCurrentUserIdentity()['avatar']);

            // fill the form with default values
            $userForm->getForm()->setData(UserIdentityService::getCurrentUserIdentity());

            // validate the form
            if ($this->getRequest()->isPost() &&
                    $this->getRequest()->getPost('form_name') == $userForm->getFormName()) {

                // make certain to merge the files info!
                $post = array_merge_recursive(
                    $this->getRequest()->getPost()->toArray(),
                    $this->getRequest()->getFiles()->toArray()
                );

                // fill the form with received values
                $userForm->getForm()->setData($post, false);

                // save data
                if ($userForm->getForm()->isValid()) {
                    // set status
                    $status = (int) $this->getSetting('user_auto_confirm') ||
                            UserIdentityService::getCurrentUserIdentity()['role'] ==  AclBaseModel::DEFAULT_ROLE_ADMIN ? true : false;

                    $deleteAvatar = (int) $this->getRequest()->getPost('avatar_delete') ? true : false;

                    // edit current user's info
                    $result = $this->getModel()->editUser(UserIdentityService::getCurrentUserIdentity(), 
                            $userForm->getForm()->getData(), $status, $this->getRequest()->getFiles()->avatar, $deleteAvatar, true);

                    if (true === $result) {
                        if ($status) {
                            $this->getFlashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->translate('Your account has been edited'));
                        }
                        else {
                            $this->getFlashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->translate('Your account will be active after checking'));

                            // redirect to login page
                            $loginUrl = $this->getView()->pageUrl('login');

                            return $this->redirectTo(['page_name' => (false !== $loginUrl ? $loginUrl : '')]);
                        }
                    }
                    else {
                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));
                    }

                    return $this->reloadPage();
                }
            }

            return $this->getView()->partial('user/widget/edit', [
                'user_form' => $userForm->getForm()
            ]);
        }

        return false;
    }
}
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
use Application\Service\ApplicationTimeZone as TimeZoneService;
use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;
use Localization\Service\Localization as LocalizationService;

class UserRegisterWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (UserIdentityService::isGuest() && (int) $this->getSetting('user_allow_register')) {
            // get an user form
            $userForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\User')
                ->setModel($this->getModel())
                ->setTimeZones(TimeZoneService::getTimeZones())
                ->showCaptcha(true);

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
                    // add a new user with a particular status
                    $status = (int) $this->getSetting('user_auto_confirm') ? true : false;

                    $userInfo = $this->getModel()->addUser($userForm->getForm()->getData(),
                            LocalizationService::getCurrentLocalization()['language'], $status, $this->getRequest()->getFiles()->avatar, true);

                    // the user has been added
                    if (is_array($userInfo)) {
                        // check the user status
                        if (!$status) {
                            // get user activate url
                            if (false !== ($activateUrl = $this->
                                    getView()->pageUrl('user-activate', ['user_id' => $userInfo['user_id']]))) {

                                // send an email activate notification
                                EmailNotificationUtility::sendNotification($userInfo['email'],
                                    $this->getSetting('user_email_confirmation_title'),
                                    $this->getSetting('user_email_confirmation_message'), [
                                        'find' => [
                                            'RealName',
                                            'SiteName',
                                            'ConfirmationLink',
                                            'ConfCode'
                                        ],
                                        'replace' => [
                                            $userInfo['nick_name'],
                                            $this->getSetting('application_site_name'),
                                            $this->getView()->url('page', ['page_name' => $activateUrl, 'slug' => $userInfo['slug']], ['force_canonical' => true]),
                                            $userInfo['activation_code']
                                        ]
                                    ], true);

                                $this->getFlashMessenger()
                                    ->setNamespace('success')
                                    ->addMessage($this->translate('We sent a message with a confirmation code to your registration e-mail'));
                            }
                            else {
                                $this->getFlashMessenger()
                                    ->setNamespace('success')
                                    ->addMessage($this->translate('Your profile will be activated after checking'));
                            }

                            $this->reloadPage();
                        }
                        else {
                            // login and redirect the registered user
                            return $this->loginUser($userInfo['user_id'], $userInfo['nick_name'], false);
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

            return $this->getView()->partial('user/widget/register', [
                'user_form' => $userForm->getForm()
            ]);
        }

        return false;
    }
}
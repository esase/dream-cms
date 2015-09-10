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
use Localization\Service\Localization as LocalizationService;
use User\Service\UserIdentity as UserIdentityService;
use Zend\EventManager\EventManagerInterface;

abstract class ApplicationAbstractAdministrationController extends ApplicationAbstractBaseController
{
    /**
     * Layout name
     *
     * @var string
     */
    protected $layout = 'layout/administration';

    /**
     * Set event manager
     *
     * @param \Zend\EventManager\EventManagerInterface $events
     * @return void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;

        // execute before executing action logic
        $events->attach('dispatch', function ($e) use ($controller) {
            // check permission
            if (!AclService::checkPermission($controller->
                    params('controller') . ' ' . $controller->params('action'), false)) {

                return UserIdentityService::isGuest()
                    ? $this->redirectTo('login-administration', 'index', [], false, ['back_url' => $this->getRequest()->getRequestUri()])
                    : $controller->showErrorPage();
            }

            // set an admin layout
            if (!$e->getRequest()->isXmlHttpRequest()) {
                $controller->layout($this->layout);
            }
        }, 100);
    }

    /**
     * Generate settings form
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return \Application\Form\ApplicationCustomFormBuilder
     */
    protected function settingsForm($module, $controller, $action)
    {
        $currentLanguage = LocalizationService::getCurrentLocalization()['language'];

        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationSetting');

        // get settings list
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationSettingAdministration');

        if (false !== ($settingsList = $settings->getSettingsList($module, $currentLanguage))) {
            $settingsForm->addFormElements($settingsList);
            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // fill the form with received values
                $settingsForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($settingsForm->getForm()->isValid()) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $settingsForm->getForm();
                    }

                    if (true === ($result = $settings->
                            saveSettings($settingsList, $settingsForm->getForm()->getData(), $currentLanguage, $module))) {

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));
                    }

                    $this->redirectTo($controller, $action);
                }
            }
        }

        return $settingsForm->getForm();
    }
}
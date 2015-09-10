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
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use User\Model\UserWidget as UserWidgetModel;

class UserForgot extends ApplicationAbstractCustomForm
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'user-forgot';

    /**
     * Model instance
     *
     * @var \User\Model\UserWidget
     */
    protected $model;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Your email',
            'required' => true
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => ApplicationCustomFormBuilder::FIELD_CAPTCHA,
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

    /**
     * Get form instance
     *
     * @return \Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // validate activation code
            $this->formElements['email']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateEmail'],
                        'message' => 'Email not found'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Validate email
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateEmail($value, array $context = [])
    {
        // get an user info
        if (null != ($userInfo =
                $this->model->getUserInfo($value, UserWidgetModel::USER_INFO_BY_EMAIL))) {

            // check the user's status
            if ($userInfo['status'] == UserWidgetModel::STATUS_APPROVED) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set a model
     *
     * @param \User\Model\UserWidget $model
     * @return \User\Form\UserForgot
     */
    public function setModel(UserWidgetModel $model)
    {
        $this->model = $model;

        return $this;
    }
}
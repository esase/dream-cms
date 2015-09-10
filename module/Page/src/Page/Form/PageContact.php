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
namespace Page\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;

class PageContact extends ApplicationAbstractCustomForm
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'contact';

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Your name',
            'required' => true
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true
        ],
        'phone' => [
            'name' => 'phone',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => false
        ],
        'message' => [
            'name' => 'message',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Message',
            'required' => true
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => ApplicationCustomFormBuilder::FIELD_CAPTCHA
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ]
    ];

    /**
     * Captcha enabled flag
     *
     * @var boolean
     */
    protected $isCaptchaEnabled = false;

    /**
     * Get form instance
     *
     * @return \Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get a form builder
        if (!$this->form) {
            // remove the captcha field
            if (!$this->isCaptchaEnabled) {
                unset($this->formElements['captcha']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method); 
        }

        return $this->form;
    }

    /**
     * Show captcha
     *
     * @param boolean $state
     * @return \Page\Form\PageContact
     */
    public function showCaptcha($state)
    {
        $this->isCaptchaEnabled = $state;

        return $this;
    }
}
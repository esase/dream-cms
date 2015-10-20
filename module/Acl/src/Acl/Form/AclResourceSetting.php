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
 */
namespace Acl\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class AclResourceSetting extends ApplicationAbstractCustomForm 
{
    /**
     * Actions limit max string length
     */
    const ACTIONS_LIMIT_MAX_LENGTH = 7;

    /**
     * Actions reset max string length
     */
    const ACTIONS_RESET_MAX_LENGTH = 7;

    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'acl-resource-settings';

    /**
     * List of ignored elements
     *
     * @var array
     */
    protected $ignoredElements = ['clean_counter'];

    /**
     * Actions limit
     *
     * @var integer
     */
    protected $actionsLimit;

    /**
     * Actions reset
     *
     * @var integer
     */
    protected $actionsReset;

    /**
     * Date start
     *
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     *
     * @var integer
     */
    protected $dateEnd;

    /**
     * Show clear action counter
     *
     * @var boolean
     */
    protected $showCleanActionCounter = false;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'actions_limit' => [
            'name' => 'actions_limit',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of allowed actions',
            'required' => false,
            'max_length' => self::ACTIONS_LIMIT_MAX_LENGTH
        ],
        'actions_reset' => [
            'name' => 'actions_reset',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of actions is reset every N seconds',
            'required' => false,
            'max_length' => self::ACTIONS_RESET_MAX_LENGTH
        ],
        'date_start' => [
            'name' => 'date_start',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available since',
            'required' => false
        ],
        'date_end' => [
            'name' => 'date_end',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available until',
            'required' => false
        ],
        'clean_counter' => [
            'name' => 'clean_counter',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Clean the action counter',
            'required' => false
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
            // fill the form with default values
            $this->formElements['actions_limit']['value'] = $this->actionsLimit;
            $this->formElements['actions_reset']['value'] = $this->actionsReset;
            $this->formElements['date_start']['value'] = $this->dateStart;
            $this->formElements['date_end']['value'] = $this->dateEnd;

            // remove the clean action counter field
            if (!$this->showCleanActionCounter) {
                unset($this->formElements['clean_counter']);
            }

            // add extra validators
            $this->formElements['actions_limit']['validators'] = [
                [
                    'name' => 'greaterThan',
                    'options' => [
                        'min' => -1
                    ]
                ]
            ];

            $this->formElements['actions_reset']['validators'] = [
                [
                    'name' => 'greaterThan',
                    'options' => [
                        'min' => -1
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set actions limit
     *
     * @param integer $actionsLimit
     * @return \Acl\Form\AclResourceSetting
     */
    public function setActionsLimit($actionsLimit)
    {
        if ((int) $actionsLimit) {
            $this->actionsLimit = $actionsLimit;
        }

        return $this;
    }

    /**
     * Set actions reset
     *
     * @param integer $actionsReset
     * @return \Acl\Form\AclResourceSetting
     */
    public function setActionsReset($actionsReset)
    {
        if ((int) $actionsReset) {
            $this->actionsReset = $actionsReset;
        }

        return $this;
    }

    /**
     * Set date start
     *
     * @param integer $dateStart
     * @return \Acl\Form\AclResourceSetting
     */
    public function setDateStart($dateStart)
    {
        if ((int) $dateStart) {
            $this->dateStart = $dateStart;
        }

        return $this;
    }

    /**
     * Set date end
     *
     * @param integer $dateEnd
     * @return \Acl\Form\AclResourceSetting
     */
    public function setDateEnd($dateEnd)
    {
        if ((int) $dateEnd) {
            $this->dateEnd = $dateEnd;
        }

        return $this;
    }

    /**
     * Show the action clean counter
     *
     * @return \Acl\Form\AclResourceSetting
     */
    public function showActionCleanCounter()
    {
        $this->showCleanActionCounter = true;

        return $this;
    }
}
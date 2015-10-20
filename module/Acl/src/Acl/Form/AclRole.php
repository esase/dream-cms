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

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Acl\Model\AclAdministration as AclAdministrationModel;

class AclRole extends ApplicationAbstractCustomForm 
{
    /**
     * ACL role name max string length
     */
    const ACL_NAME_MAX_LENGTH = 50;

    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'acl-role';

    /**
     * Model instance
     *
     * @var \Acl\Model\AclAdministration
     */
    protected $model;

    /**
     * Role id
     *
     * @var integer
     */
    protected $roleId;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'max_length' => self::ACL_NAME_MAX_LENGTH
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
            // add extra validators
            $this->formElements['name']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateRoleName'],
                        'message' => 'Role already used'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a model
     *
     * @param \Acl\Model\AclAdministration $model
     * @return \Acl\Form\AclRole
     */
    public function setModel(AclAdministrationModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set a role id
     *
     * @param integer $roleId
     * @return \Acl\Form\AclRole
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Validate a role name
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateRoleName($value, array $context = [])
    {
        return $this->model->isRoleNameFree($value, $this->roleId);
    }
}
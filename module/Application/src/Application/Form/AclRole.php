<?php

namespace Application\Form;

use Application\Form\CustomFormBuilder;

class AclRole extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-role';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'category' => 'General info',
        ),
        1 => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ),
        2 => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ),
    );
}
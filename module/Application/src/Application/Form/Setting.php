<?php

namespace Application\Form;

use Application\Form\CustomFormBuilder;

class Setting extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'settings';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Save',
        ),
        1 => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ), 
    );
}
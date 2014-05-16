<?php

namespace User\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;

class Login extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'login';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'nickname',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true
        ),
        1 => array(
            'name' => 'password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true
        ),
        2 => array(
            'name' => 'remember',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Remember me'
        ),
        3 => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ),
        4 => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ), 
    );
}
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
    protected $formElements = [
        0 => [
            'name' => 'nickname',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true
        ],
        1 => [
            'name' => 'password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true
        ],
        2 => [
            'name' => 'remember',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Remember me'
        ],
        3 => [
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ],
        4 => [
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ], 
    ];
}
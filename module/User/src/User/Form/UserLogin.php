<?php
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;

class UserLogin extends ApplicationAbstractCustomForm 
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
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true
        ],
        1 => [
            'name' => 'password',
            'type' => ApplicationCustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true
        ],
        2 => [
            'name' => 'remember',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Remember me'
        ],
        3 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ],
        4 => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ], 
    ];
}
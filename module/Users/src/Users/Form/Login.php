<?php

namespace Users\Form;
use Application\Form\AbstractCustomForm;

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
            'type' => 'text',
            'label' => 'NickName',
            'required' => true
        ),
        1 => array(
            'name' => 'password',
            'type' => 'password',
            'label' => 'Password',
            'required' => true
        ),
        2 => array(
            'name' => 'remember',
            'type' => 'checkbox',
            'label' => 'Remember me'
        ),
        3 => array(
            'name' => 'submit',
            'type' => 'submit',
            'label' => 'Save',
        ),
        4 => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ), 
    );
}
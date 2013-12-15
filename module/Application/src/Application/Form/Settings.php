<?php

namespace Application\Form;

class Settings extends AbstractCustomForm 
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
            'type' => 'submit',
            'label' => 'Save',
        ),
        1 => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ), 
    );
}
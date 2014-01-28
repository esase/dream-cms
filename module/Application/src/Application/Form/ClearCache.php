<?php

namespace Application\Form;

class ClearCache extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'clear-cache';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'cache',
            'type' => 'multicheckbox',
            'label' => 'Select a needed cache',
            'values' => array(
                'static'  => 'Static cache (DB queries, etc)',
                'dynamic' => 'Dynamic cache (translations, template paths, etc)',
                'config' => 'Application config cache',
                'js' => 'Js cache',
                'css' => 'Css cache',
            )
        ),
        1 => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ),
        2 => array(
            'name' => 'clear',
            'type' => 'submit',
            'label' => 'Clear',
        ),
    );
}
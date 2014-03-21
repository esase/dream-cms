<?php

namespace Application\Form;

use Application\Form\CustomFormBuilder;

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
            'type' => CustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Select a needed cache',
            'values' => array(
                'static'  => 'Static cache (DB queries, etc)',
                'dynamic' => 'Dynamic cache (translations, template paths, etc)',
                'config' => 'Application config cache',
                'js' => 'Js cache',
                'css' => 'Css cache',
            ),
            'category' => 'Cache types',
        ),
        1 => array(
            'name' => 'clear',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Clear',
        ),
    );
}
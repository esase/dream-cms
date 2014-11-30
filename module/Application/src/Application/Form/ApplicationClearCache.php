<?php
namespace Application\Form;

use Application\Form\ApplicationCustomFormBuilder;

class ApplicationClearCache extends ApplicationAbstractCustomForm 
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
    protected $formElements = [
        0 => [
            'name' => 'cache',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Select a needed cache',
            'values' => [
                'static'  => 'Static cache (DB queries, etc)',
                'dynamic' => 'Dynamic cache (translations, template paths, etc)',
                'config' => 'Application config cache',
                'js' => 'Js cache',
                'css' => 'Css cache'
            ],
        ],
        1 => [
            'name' => 'clear',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Clear'
        ],
    ];
}
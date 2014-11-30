<?php
namespace Application\Form;

use Application\Form\ApplicationCustomFormBuilder;

class ApplicationSetting extends ApplicationAbstractCustomForm 
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
    protected $formElements = [
        0 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Save'
        ]
    ];
}
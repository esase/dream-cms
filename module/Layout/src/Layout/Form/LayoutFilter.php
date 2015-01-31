<?php
namespace Layout\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Layout\Model\LayoutBase as LayoutBaseModel;

class LayoutFilter extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = ['submit'];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name'
        ],
        [
            'name' => 'type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Type',
            'values' => [
               LayoutBaseModel::LAYOUT_TYPE_SYSTEM => 'system',
               LayoutBaseModel::LAYOUT_TYPE_CUSTOM => 'custom'
            ]
        ],
        [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}
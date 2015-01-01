<?php
namespace Application\Form;

use Application\Model\ApplicationAbstractBase as ApplicationAbstractBaseModel;

class ApplicationModuleFilter extends ApplicationAbstractCustomForm 
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
        0 => [
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => [
               ApplicationAbstractBaseModel::MODULE_STATUS_ACTIVE => 'active',
               ApplicationAbstractBaseModel::MODULE_STATUS_NOT_ACTIVE => 'not_active'
            ]
        ],
        1 => [
            'name' => 'type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Type',
            'values' => [
               ApplicationAbstractBaseModel::MODULE_TYPE_SYSTEM => 'system',
               ApplicationAbstractBaseModel::MODULE_TYPE_CUSTOM => 'custom'
            ]
        ],
        2 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}
<?php
namespace Acl\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Acl\Model\AclBase as AclBaseModel;
use Application\Form\ApplicationCustomFormBuilder;

class AclRoleFilter extends ApplicationAbstractCustomForm 
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
            'name' => 'type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Type',
            'values' => [
               AclBaseModel::ROLE_TYPE_SYSTEM => 'system',
               AclBaseModel::ROLE_TYPE_CUSTOM => 'custom'
            ]
        ],
        1 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}
<?php

namespace Application\Form;

use Application\Model\Acl as AclModel;

class AclFilter extends AbstractCustomForm 
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
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'type',
            'type' => 'multicheckbox',
            'label' => 'Type',
            'values' => array(
               AclModel::ROLE_TYPE_SYSTEM => 'system',
               AclModel::ROLE_TYPE_CUSTOM => 'custom'
            )
        ),
        1 => array(
            'name' => 'submit',
            'type' => 'submit',
            'label' => 'Search',
        )
    );
}
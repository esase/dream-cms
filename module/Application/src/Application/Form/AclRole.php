<?php

namespace Application\Form;

use Application\Model\Acl as AclModel;

class AclRole extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-role';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'name',
            'type' => 'text',
            'label' => 'Name',
            'required' => true
        ),
        1 => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ),
        2 => array(
            'name' => 'submit',
            'type' => 'submit',
            'label' => 'Submit',
        ),
    );
}
<?php

namespace Users\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;

class Delete extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'delete-user';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'confirm',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Confirm',
            'description' => 'All of your content will be deleted. Are you sure you want to delete your account?',
            'required' => true,
            'category' => 'Deleting',
        ),
        3 => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Delete',
        ),
        4 => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ), 
    );
}
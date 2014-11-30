<?php
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;

class UserDelete extends ApplicationAbstractCustomForm 
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
    protected $formElements = [
        0 => [
            'name' => 'confirm',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Confirm',
            'description' => 'All of your content will be deleted. Are you sure you want to delete your account?',
            'required' => true
        ],
        1 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Delete',
        ],
        2 => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ], 
    ];
}
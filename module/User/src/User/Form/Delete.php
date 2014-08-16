<?php
namespace User\Form;

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
    protected $formElements = [
        0 => [
            'name' => 'confirm',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Confirm',
            'description' => 'All of your content will be deleted. Are you sure you want to delete your account?',
            'required' => true
        ],
        1 => [
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Delete',
        ],
        2 => [
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ], 
    ];
}
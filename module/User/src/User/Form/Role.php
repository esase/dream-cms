<?php

namespace User\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;
use Application\Model\Acl as AclModel;

class Role extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'user-role';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'role' => array(
            'name' => 'role',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'required' => true,
            'category' => 'List of roles'
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ),
    );

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // fill the form with default values
            $this->formElements['role']['values'] = $this->model->getRolesList();

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(AclModel $model)
    {
        $this->model = $model;
        return $this;
    }
}
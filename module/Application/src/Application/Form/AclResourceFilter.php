<?php

namespace Application\Form;

use Application\Model\AclAdministration;
use Application\Form\CustomFormBuilder;

class AclResourceFilter extends AbstractCustomForm 
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
    protected $notValidatedElements = array('submit');

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'modules' => array(
            'name' => 'modules',
            'type' => CustomFormBuilder::FIELD_MULTI_SELECT,
            'label' => 'Module',
            'values' => array(
            )
        ),
        'status' => array(
            'name' => 'status',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => array(
                'allowed'  => 'Allowed',
                'disallowed' => 'Disallowed'
            )
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        )
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
            if ($this->model) {
                // fill the form with default values
                $this->formElements['modules']['values'] = $this->model->getActiveModulesList();
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set model
     *
     * @param object $model
     * @return void
     */
    public function setModel(AclAdministration $model)
    {
        $this->model = $model;
    }
}
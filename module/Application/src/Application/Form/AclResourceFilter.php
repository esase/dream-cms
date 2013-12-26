<?php

namespace Application\Form;

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
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'modules' => array(
            'name' => 'modules',
            'type' => 'multiselect',
            'label' => 'Module',
            'values' => array(
            )
        ),
        'status' => array(
            'name' => 'status',
            'type' => 'select',
            'label' => 'Status',
            'values' => array(
                'allowed'  => 'Allowed',
                'disallowed' => 'Disallowed'
            )
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => 'submit',
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
            // file the form with default values
            $this->formElements['modules']['values'] = $this->model->getActiveModulesList();

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set model
     *
     * @param object $model
     * @return void
     */
    public function setModel(\Application\Model\AclAdministration $model)
    {
        $this->model = $model;
    }
}
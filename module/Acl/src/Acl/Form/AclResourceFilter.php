<?php
namespace Acl\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Acl\Model\AclBase as AclBaseModel;

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
     * Hide status filter
     * @var boolean
     */
    protected $hideStatusFilter;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'modules' => array(
            'name' => 'modules',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_SELECT,
            'label' => 'Module',
            'values' => array(
            )
        ),
        'status' => array(
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => array(
                AclBaseModel::ACTION_ALLOWED  => 'Allowed',
                AclBaseModel::ACTION_DISALLOWED => 'Disallowed'
            )
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
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

            // hide the status filter
            if ($this->hideStatusFilter) {
                unset($this->formElements['status']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(AclBaseModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Hide status filter
     * @return object fluent interface
     */
    public function hideStatusFilter()
    {
        $this->hideStatusFilter = true;
        return $this;
    }
}
<?php

namespace Membership\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Model\AclAdministration as AclModelAdministration;

class MembershipFilter extends AbstractCustomForm 
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
     * Acl model
     * @var object
     */
    protected $aclModel;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'cost' => array(
            'name' => 'cost',
            'type' => CustomFormBuilder::FIELD_FLOAT,
            'label' => 'Cost'
        ),
        'lifetime' => array(
            'name' => 'lifetime',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Lifetime in days'
        ),
        'role' => array(
            'name' => 'role',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Role'
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
            // file the form with default values
            if ($this->aclModel) {
                // get list of acl roles
                $this->formElements['role']['values'] = $this->aclModel->getRolesList();
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set acl model
     *
     * @param object $aclModel
     * @return object fluent interface
     */
    public function setAclModel(AclModelAdministration $aclModel)
    {
        $this->aclModel = $aclModel;
        return $this;   
    }
}
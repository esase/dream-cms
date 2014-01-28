<?php

namespace Users\Form;

use Application\Form\AbstractCustomForm;
use Users\Model\Base as BaseUsersModel;
use Application\Model\AclAdministration;
use Application\Form\CustomFormBuilder;

class UsersFilter extends AbstractCustomForm 
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
        'nickname' => array(
            'name' => 'nickname',
            'type' => 'text',
            'label' => 'NickName'
        ),
        'email' => array(
            'name' => 'email',
            'type' => 'text',
            'label' => 'Email'
        ),
        'status' => array(
            'name' => 'status',
            'type' => 'select',
            'label' => 'Status',
            'values' => array(
               BaseUsersModel::STATUS_APPROVED => 'approved',
               BaseUsersModel::STATUS_DISAPPROVED => 'disapproved'
            )
        ),
        'role' => array(
            'name' => 'role',
            'type' => 'select',
            'label' => 'Role',
            'values' => array(
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
    public function setAclModel(AclAdministration $aclModel)
    {
        $this->aclModel = $aclModel;
        return $this;   
    }
}
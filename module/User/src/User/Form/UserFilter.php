<?php

namespace User\Form;

use Application\Form\AbstractCustomForm;
use User\Model\Base as UserBaseModel;
use Application\Form\CustomFormBuilder;
use Application\Service\Service as ApplicationService;

class UserFilter extends AbstractCustomForm 
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
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'nickname' => array(
            'name' => 'nickname',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName'
        ),
        'email' => array(
            'name' => 'email',
            'type' => CustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email'
        ),
        'status' => array(
            'name' => 'status',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => array(
               UserBaseModel::STATUS_APPROVED => 'approved',
               UserBaseModel::STATUS_DISAPPROVED => 'disapproved'
            )
        ),
        'role' => array(
            'name' => 'role',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'values' => array(
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
            // get list of acl roles
            $this->formElements['role']['values'] = ApplicationService::getAclRoles();

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
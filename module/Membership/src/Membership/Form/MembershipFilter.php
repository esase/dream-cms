<?php

namespace Membership\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Service\Service as ApplicationService;
use Membership\Model\Base as MembershipBaseModel;

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
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'title' => array(
            'name' => 'title',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Title'
        ),
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
        'active' => array(
            'name' => 'active',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => array(
               MembershipBaseModel::MEMBERSHIP_LEVEL_STATUS_ACTIVE => 'approved',
               MembershipBaseModel::MEMBERSHIP_LEVEL_STATUS_NOT_ACTIVE => 'disapproved'
            )
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
            // get list of acl roles
            $this->formElements['role']['values'] = ApplicationService::getAclRoles();

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
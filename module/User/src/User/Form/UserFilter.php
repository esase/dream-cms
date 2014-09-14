<?php
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use User\Model\UserBase as UserBaseModel;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Service\Service as ApplicationService;

class UserFilter extends ApplicationAbstractCustomForm 
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
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName'
        ),
        'email' => array(
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email'
        ),
        'status' => array(
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => array(
               UserBaseModel::STATUS_APPROVED => 'approved',
               UserBaseModel::STATUS_DISAPPROVED => 'disapproved'
            )
        ),
        'role' => array(
            'name' => 'role',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'values' => array(
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
            // get list of acl roles
            $this->formElements['role']['values'] = ApplicationService::getAclRoles();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
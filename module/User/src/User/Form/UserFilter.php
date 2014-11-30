<?php
namespace User\Form;

use Acl\Service\Acl as AclService;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use User\Model\UserBase as UserBaseModel;

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
    protected $notValidatedElements = ['submit'];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'nickname' => [
            'name' => 'nickname',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName'
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email'
        ],
        'status' => [
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Status',
            'values' => [
               UserBaseModel::STATUS_APPROVED => 'approved',
               UserBaseModel::STATUS_DISAPPROVED => 'disapproved'
            ]
        ],
        'role' => [
            'name' => 'role',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'values' => [
            ]
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];

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
            $this->formElements['role']['values'] = AclService::getAclRoles();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
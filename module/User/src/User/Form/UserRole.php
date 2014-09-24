<?php
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Acl\Service\Acl as AclService;

class UserRole extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'user-role';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'role' => [
            'name' => 'role',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'required' => true
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
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
            // fill the form with default values
            $this->formElements['role']['values'] = AclService::getAclRoles();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
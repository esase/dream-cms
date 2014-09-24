<?php
namespace User\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use User\Model\UserWidget as UserWidgetModel;

class UserActivationCode extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'user-activation-code';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * User id
     * @var integer
     */
    protected $userId;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'activation_code' => [
            'name' => 'activation_code',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Activation code',
            'required' => true
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => ApplicationCustomFormBuilder::FIELD_CAPTCHA
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
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
            // validate activation code
            $this->formElements['activation_code']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateActivationCode'],
                        'message' => 'Wrong activation code'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Validate activation code
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateActivationCode($value, array $context = [])
    {
        return $this->model->checkActivationCode($this->userId, $value);
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(UserWidgetModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set a user id
     *
     * @param integer $userId
     * @return object fluent interface
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
}
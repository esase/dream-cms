<?php

namespace Users\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;
use Users\Model\User as UserModel;

class ActivationCode extends AbstractCustomForm 
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
    protected $formElements = array(
        'activation_code' => array(
            'name' => 'activation_code',
            'type' => 'text',
            'label' => 'Activation code',
            'required' => true
        ),
        'captcha' => array(
            'name' => 'captcha',
            'type' => 'captcha'
        ),
        'csrf' => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => 'submit',
            'label' => 'Submit',
        ),
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
            // validate activation code
            $this->formElements['activation_code']['validators'] = array(
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateActivationCode'),
                        'message' => 'Wrong activation code'
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
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
    public function validateActivationCode($value, array $context = array())
    {
        return $this->model->checkActivationCode($this->userId, $value);
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(UserModel $model)
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
<?php

namespace Users\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;
use Users\Model\User as UserModel;

class Forgot extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'user-forgot';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'email' => array(
            'name' => 'email',
            'type' => 'email',
            'label' => 'Your email',
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
            $this->formElements['email']['validators'] = array(
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateEmail'),
                        'message' => 'Email not found'
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Validate email
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateEmail($value, array $context = array())
    {
        // get an user info
        if (null != ($userInfo =
                $this->model->getUserInfo($value, UserModel::USER_INFO_BY_EMAIL))) {

            // check the user's status
            if ($userInfo['status'] == UserModel::STATUS_APPROVED) {
                return true;
            }
        }

        return false;
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
}
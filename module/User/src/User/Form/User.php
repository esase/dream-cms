<?php

namespace User\Form;

use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;
use DateTimeZone;
use User\Model\Base as UserBaseModel;
use Application\Service\Service as ApplicationService;

class User extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'user';

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = array('confirm_password', 'captcha', 'avatar');

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
     * User avatar
     * @var string
     */
    protected $avatar;

    /**
     * Captcha enabled flag
     * @var boolean
     */
    protected $isCaptchaEnabled = false;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'nick_name' => array(
            'name' => 'nick_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true,
            'category' => 'General info',
            'description' => 'Nickname description',
            'description_params' => array(),
        ),
        'email' => array(
            'name' => 'email',
            'type' => CustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'category' => 'General info'
        ),
        'password' => array(
            'name' => 'password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true,
            'category' => 'General info'
        ),
        'confirm_password' => array(
            'name' => 'confirm_password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Confirm password',
            'required' => true,
            'category' => 'General info'
        ),
        'captcha' => array(
            'name' => 'captcha',
            'type' => CustomFormBuilder::FIELD_CAPTCHA,
            'category' => 'General info'
        ),
        'phone' => array(
            'name' => 'phone',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => false,
            'category' => 'Miscellaneous info'
        ),
        'first_name' => array(
            'name' => 'first_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'First Name',
            'required' => false,
            'category' => 'Miscellaneous info',
        ),
        'last_name' => array(
            'name' => 'last_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Last Name',
            'required' => false,
            'category' => 'Miscellaneous info',
        ),
        'address' => array(
            'name' => 'address',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Address',
            'required' => false,
            'category' => 'Miscellaneous info',
        ),
        'time_zone' => array(
            'name' => 'time_zone',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Time zone',
            'required' => false,
            'values' => array(),
            'category' => 'Miscellaneous info',
            'description' => 'Timezone description'
        ),
        'avatar' => array(
            'name' => 'avatar',
            'type' => CustomFormBuilder::FIELD_IMAGE,
            'label' => 'Avatar',
            'required' => false,
            'extra_options' => array(
                'file_url' => null,
                'preview' => false,
                'delete_option' => true
            ),
            'category' => 'Miscellaneous info'
        ),
        'csrf' => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
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
            // remove the captcha field
            if (!$this->isCaptchaEnabled) {
                unset($this->formElements['captcha']);
            }

            // skip some required flags
            if ($this->userId) {
                $this->formElements['password']['required'] = false;
                $this->formElements['confirm_password']['required'] = false;
                $this->formElements['avatar']['required'] = false;
            }

            // add preview for the avatar
            if ($this->avatar) {
                $this->formElements['avatar']['extra_options']['preview'] = true;
                $this->formElements['avatar']['extra_options']['file_url'] =
                        ApplicationService::getResourcesUrl() . UserBaseModel::getThumbnailsDir() . $this->avatar;
            }

            // add descriptions params
            $this->formElements['nick_name']['description_params'] = array(
                ApplicationService::getSetting('user_nickname_min'),
                ApplicationService::getSetting('user_nickname_max'),
            );

            // add extra validators
            $this->formElements['confirm_password']['validators'] = array(
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validatePassword'),
                        'message' => 'Passwords do not match'
                    )
                )
            );

            $this->formElements['password']['validators'] = array(
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validatePassword'),
                        'message' => 'Passwords do not match'
                    )
                )
            );

            // validate email
            $this->formElements['email']['validators'] = array(
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateEmail'),
                        'message' => 'Email already used'
                    )
                )
            );

            // validate nickname
            $this->formElements['nick_name']['validators'] = array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'min' => (int) ApplicationService::getSetting('user_nickname_min'),
                        'max' => (int) ApplicationService::getSetting('user_nickname_max')
                    )
                ),
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateNickname'),
                        'message' => 'Nickname already used'
                    )
                )
            );

            // fill the form with default values
            $this->formElements['time_zone']['values'] = ApplicationService::getTimeZones();

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(UserBaseModel $model)
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

    /**
     * Set a user avatar
     *
     * @param string $avatar
     * @return object fluent interface
     */
    public function setUserAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * Show captcha
     *
     * @param boolean $state
     * @return object fluent interface
     */
    public function showCaptcha($state)
    {
        $this->isCaptchaEnabled = $state;
        return $this;
    }

    /**
     * Validate password
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validatePassword($value, array $context = array())
    {
        return isset($context['confirm_password'],
                $context['password']) && $context['confirm_password'] == $context['password'];
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
        return $this->model->isEmailFree($value, $this->userId);
    }

    /**
     * Validate nickname
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateNickname($value, array $context = array())
    {
        return $this->model->isNickNameFree($value, $this->userId);
    }
}
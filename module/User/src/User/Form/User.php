<?php
namespace User\Form;

use Application\Service\Setting as SettingService;
use Application\Service\Application as ApplicationService;
use Application\Form\AbstractCustomForm;
use Application\Form\CustomFormBuilder;
use User\Model\Base as UserBaseModel;

class User extends AbstractCustomForm 
{
    /**
     * Email max string length
     */
    const EMAIL_MAX_LENGTH = 50;

    /**
     * Password max string length
     */
    const PASSWORD_MAX_LENGTH = 50;

    /**
     * Phone max string length
     */
    const PHONE_MAX_LENGTH = 50;

    /**
     * First name max string length
     */
    const FIRST_NAME_MAX_LENGTH = 100;

    /**
     * Last name max string length
     */
    const LAST_NAME_MAX_LENGTH = 100;

    /**
     * Address max string length
     */
    const ADDRESS_MAX_LENGTH = 100;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'user';

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = ['confirm_password', 'captcha', 'avatar'];

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Time zones
     * @var array
     */
    protected $timeZones;

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
    protected $formElements = [
        'nick_name' => [
            'name' => 'nick_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true,
            'category' => 'General info',
            'description' => 'Nickname description',
            'description_params' => []
        ],
        'email' => [
            'name' => 'email',
            'type' => CustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::EMAIL_MAX_LENGTH
        ],
        'password' => [
            'name' => 'password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::PASSWORD_MAX_LENGTH
        ],
        'confirm_password' => [
            'name' => 'confirm_password',
            'type' => CustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Confirm password',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::PASSWORD_MAX_LENGTH
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => CustomFormBuilder::FIELD_CAPTCHA,
            'category' => 'General info'
        ],
        'phone' => [
            'name' => 'phone',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::PHONE_MAX_LENGTH
        ],
        'first_name' => [
            'name' => 'first_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'First Name',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::FIRST_NAME_MAX_LENGTH
        ],
        'last_name' => [
            'name' => 'last_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Last Name',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::LAST_NAME_MAX_LENGTH
        ],
        'address' => [
            'name' => 'address',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Address',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::ADDRESS_MAX_LENGTH
        ],
        'time_zone' => [
            'name' => 'time_zone',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Time zone',
            'required' => false,
            'values' => [],
            'category' => 'Miscellaneous info',
            'description' => 'Timezone description'
        ],
        'avatar' => [
            'name' => 'avatar',
            'type' => CustomFormBuilder::FIELD_IMAGE,
            'label' => 'Avatar',
            'required' => false,
            'extra_options' => [
                'file_url' => null,
                'preview' => false,
                'delete_option' => true
            ],
            'category' => 'Miscellaneous info'
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
        ],
        'submit' => [
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ],
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
            $this->formElements['nick_name']['description_params'] = [
                SettingService::getSetting('user_nickname_min'),
                SettingService::getSetting('user_nickname_max'),
            ];

            // add extra validators
            $this->formElements['confirm_password']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validatePassword'],
                        'message' => 'Passwords do not match'
                    ]
                ]
            ];

            $this->formElements['password']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validatePassword'],
                        'message' => 'Passwords do not match'
                    ]
                ]
            ];

            // validate email
            $this->formElements['email']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateEmail'],
                        'message' => 'Email already used'
                    ]
                ]
            ];

            // validate nickname
            $this->formElements['nick_name']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateNickname'],
                        'message' => 'Nickname already used'
                    ]
                ]
            ];

            $this->formElements['nick_name']['max_length'] = (int) SettingService::getSetting('user_nickname_max');
            $this->formElements['nick_name']['min_length'] = (int) SettingService::getSetting('user_nickname_min');

            // fill the form with default values
            $this->formElements['time_zone']['values'] = $this->timeZones;

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
     * Set time zones
     *
     * @param array $timeZones
     * @return object fluent interface
     */
    public function setTimeZones(array $timeZones)
    {
        $this->timeZones = $timeZones;
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
    public function validatePassword($value, array $context = [])
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
    public function validateEmail($value, array $context = [])
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
    public function validateNickname($value, array $context = [])
    {
        return $this->model->isNickNameFree($value, $this->userId);
    }
}
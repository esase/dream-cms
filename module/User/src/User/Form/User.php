<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace User\Form;

use Application\Service\ApplicationSetting as SettingService;
use Application\Service\Application as ApplicationService;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use User\Model\UserBase as UserBaseModel;

class User extends ApplicationAbstractCustomForm
{
    /**
     * Slug max string length
     */
    const SLUG_MAX_LENGTH = 100;

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
     *
     * @var string
     */
    protected $formName = 'user';

    /**
     * List of ignored elements
     *
     * @var array
     */
    protected $ignoredElements = ['confirm_password', 'captcha', 'avatar'];

    /**
     * Model instance
     *
     * @var \User\Model\UserBase
     */
    protected $model;

    /**
     * Time zones
     *
     * @var array
     */
    protected $timeZones;

    /**
     * User id
     *
     * @var integer
     */
    protected $userId;

    /**
     * User avatar
     *
     * @var string
     */
    protected $avatar;

    /**
     * Captcha enabled flag
     *
     * @var boolean
     */
    protected $isCaptchaEnabled = false;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'nick_name' => [
            'name' => 'nick_name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'NickName',
            'required' => true,
            'category' => 'General info',
            'description' => 'Nickname description',
            'description_params' => []
        ],
        'slug' => [
            'name' => 'slug',
            'type' => ApplicationCustomFormBuilder::FIELD_SLUG,
            'label' => 'Display name',
            'required' => false,
            'max_length' => self::SLUG_MAX_LENGTH,
            'category' => 'General info',
            'description' => 'The display name will be displayed in the browser bar'
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::EMAIL_MAX_LENGTH
        ],
        'password' => [
            'name' => 'password',
            'type' => ApplicationCustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::PASSWORD_MAX_LENGTH
        ],
        'confirm_password' => [
            'name' => 'confirm_password',
            'type' => ApplicationCustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Confirm password',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::PASSWORD_MAX_LENGTH
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => ApplicationCustomFormBuilder::FIELD_CAPTCHA,
            'category' => 'General info'
        ],
        'phone' => [
            'name' => 'phone',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::PHONE_MAX_LENGTH
        ],
        'first_name' => [
            'name' => 'first_name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'First Name',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::FIRST_NAME_MAX_LENGTH
        ],
        'last_name' => [
            'name' => 'last_name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Last Name',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::LAST_NAME_MAX_LENGTH
        ],
        'address' => [
            'name' => 'address',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Address',
            'required' => false,
            'category' => 'Miscellaneous info',
            'max_length' => self::ADDRESS_MAX_LENGTH
        ],
        'time_zone' => [
            'name' => 'time_zone',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Time zone',
            'required' => false,
            'values' => [],
            'category' => 'Miscellaneous info',
            'description' => 'Timezone description'
        ],
        'avatar' => [
            'name' => 'avatar',
            'type' => ApplicationCustomFormBuilder::FIELD_IMAGE,
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
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ],
    ];

    /**
     * Get form instance
     *
     * @return \Application\Form\ApplicationCustomFormBuilder
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
            $this->formElements['slug']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateSlug'],
                        'message' => 'Display name already used'
                    ]
                ]
            ];

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

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a model
     *
     * @param \User\Model\UserBase $model
     * @return \User\Form\User
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
     * @return \User\Form\User
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
     * @return \User\Form\User
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
     * @return \User\Form\User
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
     * @return \User\Form\User
     */
    public function showCaptcha($state)
    {
        $this->isCaptchaEnabled = $state;

        return $this;
    }

    /**
     * Validate slug
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateSlug($value, array $context = [])
    {
        return $this->model->isSlugFree($value, $this->userId);
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
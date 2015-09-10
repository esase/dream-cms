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
namespace Application\Form;

use Application\Service\Application as ApplicationService;
use Application\Utility\ApplicationFtp as ApplicationFtpUtility;
use Exception;

class ApplicationModule extends ApplicationAbstractCustomForm
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'module';

    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * FTP utility
     *
     * @var \Application\Utility\ApplicationFtp|boolean
     */
    protected $ftpUtility = false;

    /**
     * Delete mode
     *
     * @var boolean
     */
    protected $deleteMode = false;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'login' => [
            'name' => 'login',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Login',
            'required' => true,
            'category' => 'FTP access'
        ],
        'password' => [
            'name' => 'password',
            'type' => ApplicationCustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true,
            'category' => 'FTP access'
        ],
        'file' => [
            'name' => 'module',
            'type' => ApplicationCustomFormBuilder::FIELD_FILE,
            'label' => 'Module',
            'required' => true,
            'category' => 'FTP access',
            'description' => 'Upload module description'
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
     * @return ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // add extra validators
            $this->formElements['login']['validators'] = [
                [
                    'name' => 'callback',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'callback' => [$this, 'validateFtpConnection'],
                        'message' => 'FTP server is not responding or you entered wrong login data'
                    ]
                ],
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateFtpSystemDirs'],
                        'message' => 'Your FTP account does not allow use the system dirs'
                    ]
                ]
            ];

            if (!$this->deleteMode) {
                $this->formElements['file']['validators'] = [
                    [
                        'name' => 'fileextension',
                        'options' => [
                            'extension' => 'zip'
                        ]
                    ]
                ];
            }
            else {
                unset($this->formElements['file']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a host
     *
     * @param string $host
     * @return \Application\Form\ApplicationModule
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set delete mode
     *
     * @return \Application\Form\ApplicationModule
     */
    public function setDeleteMode()
    {
        $this->deleteMode = true;

        return $this;
    }

    /**
     * Validate FTP connection
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateFtpConnection($value, array $context = [])
    {
        try {
            $this->connectToFtp($value, $context['password']);
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Validate FTP system dirs
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateFtpSystemDirs($value, array $context = [])
    {
        try {
            $ftpUtility = $this->connectToFtp($value, $context['password']);

            if ($ftpUtility->isDirExists(ApplicationService::getModulePath(false)) && $ftpUtility->
                        isDirExists(basename(APPLICATION_PUBLIC) . '/' . ApplicationService::getBaseLayoutPath(false))) {

                return true;
            }
        }
        catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Connect to FTP server
     *
     * @param string $login
     * @param string $password
     * @return \Application\Utility\ApplicationFtp
     */
    protected function connectToFtp($login, $password)
    {
        if (false === $this->ftpUtility) {
            $this->ftpUtility = new ApplicationFtpUtility($this->host, $login, $password);
        }

        return $this->ftpUtility;
    }
}
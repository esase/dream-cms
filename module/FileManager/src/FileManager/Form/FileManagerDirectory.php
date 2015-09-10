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
namespace FileManager\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Service\ApplicationSetting as SettingService;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;

class FileManagerDirectory extends ApplicationAbstractCustomForm
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'directory';

    /**
     * Current path
     *
     * @var string
     */
    protected $path;

    /**
     * Model
     *
     * @var \FileManager\Model\FileManagerBase
     */
    protected $model;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'description' => 'New directory description',
            'description_params' => []
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
     * @return \Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // add extra filters
            $this->formElements['name']['filters'] = [
                [
                    'name' => 'stringtolower'
                ]
            ];

            // add descriptions params
            $this->formElements['name']['description_params'] = [
                SettingService::getSetting('file_manager_file_name_length')
            ];

            // add extra validators
            $this->formElements['name']['validators'] = [
                [
                    'name' => 'regex',
                    'options' => [
                        'pattern' => '/^[' . FileManagerBaseModel::getDirectoryNamePattern() . ']+$/',
                        'message' => 'You can use only latin, numeric and underscore symbols'
                    ]
                ],
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateExistingDirectory'],
                        'message' => 'Directory already exist'
                    ]
                ]
            ];

            // add a directory name length limit
            $this->formElements['name']['max_length'] = (int) SettingService::getSetting('file_manager_file_name_length');

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set current path
     *
     * @param string $path
     * @return \FileManager\Form\FileManagerDirectory
     */
    public function setPath($path)
    {
        $this->path = FileManagerBaseModel::processDirectoryPath($path);

        return $this;
    }

    /**
     * Set model
     *
     * @param \FileManager\Model\FileManagerBase $model
     * @return \FileManager\Form\FileManagerDirectory
     */
    public function setModel(FileManagerBaseModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Validate existing directory
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateExistingDirectory($value, array $context = [])
    {
        // get a full path
        if (false !== ($fullPath = $this->model->getUserDirectory($this->path))) {
            if (file_exists($fullPath . '/' . $value)) {
                return !is_dir($fullPath . '/' . $value);
            }

            return true;
        }

        return false;
    }
}
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
namespace Page\Form;

use Application\Service\ApplicationSetting as SettingService;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Acl\Service\Acl as AclService;
use Page\Model\PageWidgetSetting as PageWidgetSettingModel;

class PageWidgetSetting extends ApplicationAbstractCustomForm
{
    /**
     * Title max string length
     */
    const TITLE_MAX_LENGTH = 50;

    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'settings';

    /**
     * Show visibility settings
     *
     * @var boolean
     */
    protected $showVisibilitySettings = true;

    /**
     * Show cache settings
     *
     * @var boolean
     */
    protected $showCacheSettings = false;

    /**
     * Widget description
     *
     * @var string
     */
    protected $widgetDescription;

    /**
     * Model instance
     *
     * @var \Page\Model\PageWidgetSetting
     */
    protected $model;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'title' => [
            'name' => 'title',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Widget title',
            'description' => 'The widget uses the system default title',
            'description_params' => [],
            'required' => false,
            'max_length' => self::TITLE_MAX_LENGTH,
            'category' => 'Main settings'
        ],
        'layout' => [
            'name' => 'layout',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Widget layout',
            'required' => false,
            'values' => [],
            'category' => 'Main settings',
        ],
        'visibility_settings' => [
            'name' => 'visibility_settings',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Widget is hidden for',
            'required' => false,
            'values' => [],
            'category' => 'Visibility settings'
        ],
        'cache_ttl' => [
            'name' => 'cache_ttl',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Cache lifetime in seconds',
            'required' => false,
            'values' => [],
            'category' => 'Cache',
            'description' => 'Widget cache description',
            'description_params' => []
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Save'
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
            // add extra options for the title
            $this->formElements['title']['description_params'] = [
                $this->widgetDescription
            ];

            // add extra options for the cache ttl
            if ($this->showCacheSettings) {
                $this->formElements['cache_ttl']['description_params'] = [
                    (int) SettingService::getSetting('application_dynamic_cache_life_time')
                ];

                // add extra validators
                $this->formElements['cache_ttl']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateCacheTtl'],
                            'message' => 'Enter a correct value'
                        ]
                    ]
                ];
            }
            else {
                unset($this->formElements['cache_ttl']);
            }

            // add extra options for the visibility settings
            if ($this->showVisibilitySettings) {
                // add visibility settings
                $this->formElements['visibility_settings']['values'] = AclService::getAclRoles(false, true);
            }
            else {
                unset($this->formElements['visibility_settings']);
            }

            // fill the form with default values
            $this->formElements['layout']['values'] = $this->model->getWidgetLayouts();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Validate cache ttl
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCacheTtl($value, array $context = [])
    {
        $value = (int) $value;

        return $value >= 0 && $value <= (int) SettingService::getSetting('application_dynamic_cache_life_time');
    }

    /**
     * Show visibility settings
     *
     * @param boolean $show
     * @return \Page\Form\PageWidgetSetting
     */
    public function showVisibilitySettings($show)
    {
        $this->showVisibilitySettings = (bool) $show;

        return $this;
    }

    /**
     * Show cache settings
     *
     * @param boolean $show
     * @return \Page\Form\PageWidgetSetting
     */
    public function showCacheSettings($show)
    {
        $this->showCacheSettings = (bool) $show;

        return $this;
    }

    /**
     * Set a model
     *
     * @param \Page\Model\PageWidgetSetting $model
     * @return \Page\Form\PageWidgetSetting
     */
    public function setModel(PageWidgetSettingModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set a widget description
     *
     * @param string $widgetDescription
     * @return \Page\Form\PageWidgetSetting
     */
    public function setWidgetDescription($widgetDescription)
    {
        $this->widgetDescription = $widgetDescription;

        return $this;
    }
}
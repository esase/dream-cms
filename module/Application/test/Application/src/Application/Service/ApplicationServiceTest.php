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
namespace Application\Test\Service;

use Application\Service\ApplicationSetting as SettingService;
use Application\Test\ApplicationBootstrap;
use Localization\Service\Localization as LocalizationService;
use PHPUnit_Framework_TestCase;
use ReflectionProperty;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * List of settings name
     *
     * @var array
     */
    protected $settingsNames;

    /**
     * Setting model
     *
     * @var \Application\Model\ApplicationSetting
     */
    protected $settingModel;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = ApplicationBootstrap::getServiceLocator();

        // get setting model
        $this->settingModel = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationSetting');

        // clear settings array
        $reflectionProperty = new ReflectionProperty($this->settingModel, 'settings');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // clear test settings
        if ($this->settingsNames) {
            $query = $this->settingModel->delete()
                ->from('application_setting')
                ->where(['name' => $this->settingsNames]);

            $statement = $this->settingModel->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->settingsNames = [];
        }
    }

    /**
     * Add setting
     *
     * @param string 
     * @param array $settingValues
     * @param integer $moduleId
     * @return void
     */
    protected function addSetting($setting, array $settingValues = [], $moduleId = 1)
    {
        $settingData = [
            'name' => $setting,
            'module' => $moduleId
        ];

        $query = $this->settingModel->insert()
            ->into('application_setting')
            ->values($settingData);

        $statement = $this->settingModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $settingId = $this->settingModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // add setting values
        if ($settingValues) {
            foreach ($settingValues as $settingValue) {
                // insert setting value
                $query = $this->settingModel->insert()
                    ->into('application_setting_value')
                    ->values(array_merge($settingValue, ['setting_id' => $settingId]));

                $statement = $this->settingModel->prepareStatementForSqlObject($query);
                $statement->execute(); 
            }
        }
    }

    /**
     * Test base settings. Only based settings should be returned
     */
    public function testBaseSettings()
    {
        // list of test settings
        $this->settingsNames = [
            'test language setting'
        ];

        $baseValue = time();

        // list of settings values
        $settingValues = [];
        $settingValues[] = [
            'value' => $baseValue
        ];

        // get current language
        $currentLocalization = LocalizationService::getCurrentLocalization();

        // get localization model
        $localization = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('Localization\Model\LocalizationBase');

        // process all registered localization
        foreach ($localization->getAllLocalizations() as $localizationInfo) {
            if ($currentLocalization['locale'] == $localizationInfo['locale']) {
                continue;
            }

            $settingValues[] = [
                'value' => $localizationInfo['locale'],
                'language' => $localizationInfo['language']
            ];
        }

        // add test settings
        foreach ($this->settingsNames as $settingName) {
            $this->addSetting($settingName, $settingValues);
        }

        // check settings
        foreach ($this->settingsNames as $setting) {
            $this->assertEquals(SettingService::getSetting($setting), $baseValue);
        }
    }

    /**
     * Test setting by language
     */
    public function testSettingsByLanguage()
    {
        // list of test settings
        $this->settingsNames = [
            'test language setting'
        ];

        // get localization model
        $localization = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('Localization\Model\LocalizationBase');

        // list of settings values
        $settingValues = [];
        $settingValues[] = [
            'value' => 'base'
        ];

        // process all registered localization
        foreach ($localization->getAllLocalizations() as $localizationInfo) {
            $settingValues[] = [
                'value' => $localizationInfo['locale'],
                'language' => $localizationInfo['language']
            ];
        }

        // add test settings
        foreach ($this->settingsNames as $settingName) {
            $this->addSetting($settingName, $settingValues);
        }

        // get current language
        $currentLocalization = LocalizationService::getCurrentLocalization();

        // check settings
        foreach ($this->settingsNames as $setting) {
            $this->assertEquals(SettingService::getSetting($setting), $currentLocalization['locale']);
        }
    }

    /**
     * Test not exist settings
     */
    public function testNotExistSettings()
    {
        $this->settingsNames = [
            'test language setting',
            'test acl setting'
        ];

        // check setting
        foreach ($this->settingsNames as $setting) {
            $this->assertFalse(SettingService::getSetting($setting));
        }
    }
}

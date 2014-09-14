<?php
namespace Application\Test\Service;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Application\Service\ApplicationSetting as SettingService;
use Localization\Service\Localization as LocalizationService;

use ReflectionProperty;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * List of settings name
     * @var array
     */
    protected $settingsNames;

    /**
     * Setting model
     * @var object
     */
    protected $settingModel;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = ApplicationBootstrap::getServiceManager();

        // get setting model
        $this->settingModel = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationSetting');

        // clear settings array
        $reflectionProperty = new ReflectionProperty($this->settingModel, 'settings');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
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
                ->where(array('name' => $this->settingsNames));

            $statement = $this->settingModel->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->settingsNames = array();
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
    protected function addSetting($setting, array $settingValues = array(), $moduleId = 1)
    {
        $settingData = array(
            'name' => $setting,
            'module' => $moduleId
        );

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
                    ->values(array_merge($settingValue, array('setting_id' => $settingId)));

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
        $this->settingsNames = array(
            'test language setting'
        );

        $baseValue = time();

        // list of settings values
        $settingValues = array();
        $settingValues[] = array(
            'value' => $baseValue
        );

        // get current language
        $currentLocalization = LocalizationService::getCurrentLocalization();

        // get localization model
        $localization = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Localization\Model\LocalizationBase');

        // process all registered localization
        foreach ($localization->getAllLocalizations() as $localizationInfo) {
            if ($currentLocalization['locale'] == $localizationInfo['locale']) {
                continue;
            }

            $settingValues[] = array(
                'value' => $localizationInfo['locale'],
                'language' => $localizationInfo['language']
            );
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
        $this->settingsNames = array(
            'test language setting'
        );

        // get localization model
        $localization = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Localization\Model\LocalizationBase');

        // list of settings values
        $settingValues = array();
        $settingValues[] = array(
            'value' => 'base'
        );

        // process all registered localization
        foreach ($localization->getAllLocalizations() as $localizationInfo) {
            $settingValues[] = array(
                'value' => $localizationInfo['locale'],
                'language' => $localizationInfo['language']
            );
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
        $this->settingsNames = array(
            'test language setting',
            'test acl setting'
        );

        // check setting
        foreach ($this->settingsNames as $setting) {
            $this->assertFalse(SettingService::getSetting($setting));
        }
    }
}

<?php

namespace Application\Test\Controller;

use Application\Test\ApplicationBootstrap;
use Application\Model\Localization;
use PHPUnit_Framework_TestCase;

class LocalizationTest extends PHPUnit_Framework_TestCase
{
    /**
     * Localization
     * @var object
     */
    protected $localization;

    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Config
     * @var array
     */
    protected $config;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = ApplicationBootstrap::getServiceManager();

        // get config
        $this->config = $this->serviceManager->get('Config');

        // get localization instance
        $this->localization = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Localization');
    }

    /**
     * Test localization list
     */
    public function testLocalizationList()
    {
        $this->assertNotEmpty($this->localization->getAllLocalizations());
    }
}

<?php

namespace Application\Test\Service;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Application\Utility\Locale as LocaleUtility;
use Locale;
use IntlDateFormatter;

class LocaleTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Setup
     */
    protected function setUp()
    {
        $this->serviceManager = ApplicationBootstrap::getServiceManager();
    }

    /**
     * Test convert float values to localized values
     */
    public function testConvertFloatToLocalizedValues()
    {
        $this->setLocale('ru_RU');
        $this->assertEquals('0,9', LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));

        $this->setLocale('en_US');
        $this->assertEquals(0.9, LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));

        $this->setLocale('fr_FR');
        $this->assertEquals('0,9', LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));
    }

    /**
     * Test convert date values to localized values
     */
    public function testConvertDateToLocalizedValues()
    {
        $this->setLocale('ru_RU');
        $this->assertEquals('16.03.2012', LocaleUtility::convertToLocalizedValue('2012-03-16', 'date', IntlDateFormatter::MEDIUM));

        $this->setLocale('en_US');
        $this->assertEquals('Mar 16, 2012', LocaleUtility::convertToLocalizedValue('2012-03-16', 'date', IntlDateFormatter::MEDIUM));

        $this->setLocale('fr_FR');
        $this->assertEquals('16 mars 2012', LocaleUtility::convertToLocalizedValue('2012-03-16', 'date', IntlDateFormatter::MEDIUM));
    }

    /**
     * Test convert date values from localized values
     */
    public function testConvertDateFromLocalizedValues()
    {
        $this->setLocale('ru_RU');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('16.03.2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));

        $this->setLocale('en_US');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('Mar 16, 2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));

        $this->setLocale('fr_FR');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('16 mars 2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));
    }

    /**
     * Test convert float values from localized values to internal
     */
    public function testConvertFloatFromLocalizedValues()
    {
        $this->setLocale('ru_RU');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue('0,9', 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));

        $this->setLocale('en_US');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));

        $this->setLocale('fr_FR');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue('0,9', 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return void
     */
    protected function setLocale($locale)
    {
        Locale::setDefault($locale);
        LocaleUtility::setLocale($locale);
    }
}

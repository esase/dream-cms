<?php
namespace Localization\Test\Service;

use Localization\Test\LocalizationBootstrap;
use PHPUnit_Framework_TestCase;
use Localization\Utility\LocalizationLocale as LocaleUtility;
use Locale;
use IntlDateFormatter;

class LocalizationLocaleTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Setup
     */
    protected function setUp()
    {
        $this->serviceLocator = LocalizationBootstrap::getServiceLocator();
    }

    /**
     * Test convert float values to localized values
     */
    public function testConvertFloatToLocalizedValues()
    {
        $this->setCustomLocale('ru_RU');
        $this->assertEquals('0,9', LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));

        $this->setCustomLocale('en_US');
        $this->assertEquals(0.9, LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));

        $this->setCustomLocale('fr_FR');
        $this->assertEquals('0,9', LocaleUtility::convertToLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertToLocalizedValue(1, 'float'));
    }

    /**
     * Test convert date values to localized values
     */
    public function testConvertDateToLocalizedValues()
    {
        $this->setCustomLocale('ru_RU');
        $this->assertEquals('21 мая 2014 г.', LocaleUtility::convertToLocalizedValue('2014-05-21', 'date', IntlDateFormatter::MEDIUM));

        $this->setCustomLocale('en_US');
        $this->assertEquals('Mar 16, 2012', LocaleUtility::convertToLocalizedValue('2012-03-16', 'date', IntlDateFormatter::MEDIUM));

        $this->setCustomLocale('fr_FR');
        $this->assertEquals('16 mars 2012', LocaleUtility::convertToLocalizedValue('2012-03-16', 'date', IntlDateFormatter::MEDIUM));
    }

    /**
     * Test convert date values from localized values
     */
    public function testConvertDateFromLocalizedValues()
    {
        $this->setCustomLocale('ru_RU');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('16.03.2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));

        $this->setCustomLocale('en_US');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('Mar 16, 2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));

        $this->setCustomLocale('fr_FR');
        $this->assertEquals('2012-03-16', LocaleUtility::convertFromLocalizedValue('16 mars 2012', 'date',
                IntlDateFormatter::MEDIUM, 'Y-m-d'));
    }

    /**
     * Test convert float values from localized values to internal
     */
    public function testConvertFloatFromLocalizedValues()
    {
        $this->setCustomLocale('ru_RU');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue('0,9', 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));

        $this->setCustomLocale('en_US');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue(0.9, 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));

        $this->setCustomLocale('fr_FR');
        $this->assertEquals(0.9, LocaleUtility::convertFromLocalizedValue('0,9', 'float'));
        $this->assertEquals(1, LocaleUtility::convertFromLocalizedValue(1, 'float'));
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return void
     */
    protected function setCustomLocale($locale)
    {
        Locale::setDefault($locale);
        LocaleUtility::setLocale($locale);
    }
}

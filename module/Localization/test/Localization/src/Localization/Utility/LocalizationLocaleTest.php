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
namespace Localization\Test\Service;

use Localization\Test\LocalizationBootstrap;
use Localization\Utility\LocalizationLocale as LocaleUtility;
use PHPUnit_Framework_TestCase;
use Locale;
use IntlDateFormatter;

class LocalizationLocaleTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
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

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
namespace Localization\Utility;

use Localization\Service\Localization as LocalizationService;
use Zend\I18n\Filter\NumberFormat;
use IntlDateFormatter;
use NumberFormatter;

class LocalizationLocale
{
    /**
     * Max fraction digits
     */
    const MAX_FRACTION_DIGITS = 100;

    /**
     * Current locale
     *
     * @var string
     */
    protected static $locale;

    /**
     * Get current locale
     *
     * @return string
     */
    public static function getLocale()
    {
        if (!self::$locale) {
            self::$locale = LocalizationService::getCurrentLocalization()['locale'];
        }

        return self::$locale;
    }

    /**
     * Set current locale
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale($locale)
    {
        return self::$locale = $locale;
    }

    /**
     * Convert to localized value from internal format
     *
     * @param string $value
     * @param string $type
     * @param integer $outputDateFormat (Intl date format - http://www.php.net/manual/en/book.intl.php)
     * @param string $locale
     * @return string
     */
    public static function convertToLocalizedValue($value, $type, $outputDateFormat = IntlDateFormatter::MEDIUM, $locale = null)
    {
        if (!$value) {
            return $value;
        }

        switch ($type) {
            case 'float' :
                $filter = new NumberFormat(($locale ? $locale : self::getLocale()));
                $filter->getFormatter()
                        ->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, self::MAX_FRACTION_DIGITS);

                return $filter->filter((float) $value);

            case 'date' :
            case 'date_unixtime' :
                $dateFormatter = new IntlDateFormatter(
                    ($locale ? $locale : self::getLocale()),
                    $outputDateFormat,
                    IntlDateFormatter::NONE,
                    date_default_timezone_get(),
                    IntlDateFormatter::GREGORIAN
                );

                return $type == 'date'
                    ? $dateFormatter->format(strtotime($value))
                    : $dateFormatter->format((int) $value);

            default :
                return $value;
        }
    }

    /**
     * Convert from localized value for internal usage
     *
     * @param string $value
     * @param string $type
     * @param integer $inputDateFormat (Intl date format - http://www.php.net/manual/en/book.intl.php)
     * @param string $outputDateFormat (php date format)
     * @param string $locale
     * @return mixed
     */
    public static function convertFromLocalizedValue($value, $type,
            $inputDateFormat = IntlDateFormatter::MEDIUM, $outputDateFormat = 'Y-m-d', $locale = null)
    {
        if (!$value) {
            return $value;
        }

        switch ($type) {
            case 'float' :
                $filter = new NumberFormat();

                return $filter->filter($value);

            case 'date' :
            case 'date_unixtime' :
                $dateFormatter = new IntlDateFormatter(
                    ($locale ? $locale : self::getLocale()),
                    $inputDateFormat,
                    IntlDateFormatter::NONE,
                    date_default_timezone_get(),
                    IntlDateFormatter::GREGORIAN
                );

                return $type == 'date'
                    ? date($outputDateFormat, $dateFormatter->parse($value)) // return parsed date
                    : $dateFormatter->parse($value); // return timestamp

            default :
                return $value;
        }
    }
}
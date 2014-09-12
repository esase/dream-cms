<?php
namespace Localization\Utility;

use Localization\Service\Localization as LocalizationService;
use Zend\I18n\Filter\NumberFormat;
use IntlDateFormatter;
use NumberFormatter;

class Locale
{
    /**
     * Max fraction digits
     */
    const MAX_FRACTION_DIGITS = 100;

    /**
     * Current locale
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
     * @param string $outputDateFormat (Intl date format - http://www.php.net/manual/en/book.intl.php)
     * @return string
     */
    public static function convertToLocalizedValue($value, $type, $outputDateFormat = IntlDateFormatter::MEDIUM)
    {
        if (!$value) {
            return $value;
        }

        switch ($type) {
            case 'float' :
                $filter = new NumberFormat(self::getLocale());
                $filter->getFormatter()
                        ->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, self::MAX_FRACTION_DIGITS);

                return $filter->filter((float) $value);
            case 'date' :
            case 'date_unixtime' :
                $dateFormater = new IntlDateFormatter(
                    self::getLocale(),
                    $outputDateFormat,
                    IntlDateFormatter::NONE,
                    date_default_timezone_get(),
                    IntlDateFormatter::GREGORIAN
                );

                return $type == 'date'
                    ? $dateFormater->format(strtotime($value))
                    : $dateFormater->format((int) $value);
            default :
                return $value;
        }
    }

    /**
     * Convert from localized value for internal usage
     *
     * @param string $value
     * @param string $type
     * @param string $inputDateFormat (Intl date format - http://www.php.net/manual/en/book.intl.php)
     * @param string $outputDateFormat (php date format)
     * @return mixed
     */
    public static function convertFromLocalizedValue($value, $type, $inputDateFormat = IntlDateFormatter::MEDIUM, $outputDateFormat = 'Y-m-d')
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
                $dateFormater = new IntlDateFormatter(
                    self::getLocale(),
                    $inputDateFormat,
                    IntlDateFormatter::NONE,
                    date_default_timezone_get(),
                    IntlDateFormatter::GREGORIAN
                );

                return $type == 'date'
                    ? date($outputDateFormat, $dateFormater->parse($value)) // return parsed date
                    : $dateFormater->parse($value); // return timestamp
            default :
                return $value;
        }
    }
}
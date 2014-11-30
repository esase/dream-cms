<?php
namespace Application\View\Helper;

use Application\Service\ApplicationSetting as SettingService;
use Localization\Utility\LocalizationLocale as LocaleUtility;
use Zend\View\Helper\AbstractHelper;
use IntlDateFormatter;

class ApplicationDate extends AbstractHelper
{
    /**
     * Get date
     *
     * @param string|integer $date
     * @param array $options
     *      string  type (date or date_unixtime)
     *      string format (full, long, medium, short)
     * @return string
     */
    public function __invoke($date, array $options = [])
    {
        $type = !empty($options['type']) && $options['type'] == 'date'
            ? 'date'
            : 'date_unixtime';

        if ($type == 'date_unixtime' && !(int) $date) {
            return;
        }

        $format = isset($options['format'])
            ? $options['format']
            : SettingService::getSetting('application_default_date_format');

        $format = strtolower($format);

        switch ($format) {
            case 'full' :
                $format = IntlDateFormatter::FULL;
                break;
            case 'long' :
                $format = IntlDateFormatter::LONG;
                break;
            case 'medium' :
                $format = IntlDateFormatter::MEDIUM;
                break;
            case 'short' :
            default :
                $format = IntlDateFormatter::SHORT;
        }

        return LocaleUtility::convertToLocalizedValue($date, $type, $format);
    }
}

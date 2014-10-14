<?php
namespace Application\Service;

use Localization\Service\Localization as LocalizationService;

class ApplicationSetting
{
    /**
     * Get setting
     *
     * @param string $settingName
     * @param string $language
     * @return string|boolean
     */
    public static function getSetting($settingName, $language = null)
    {
        $settingsModel = ApplicationServiceLocator::getServiceLocator()
           ->get('Application\Model\ModelManager')
           ->getInstance('Application\Model\ApplicationSetting');

        return $settingsModel->getSetting($settingName,
                ($language ? $language : LocalizationService::getCurrentLocalization()['language']));
    }
}
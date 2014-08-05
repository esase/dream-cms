<?php
namespace Application\Service;

use Localization\Service\Localization;

class Setting
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
        $settingsModel = ServiceManager::getServiceManager()
           ->get('Application\Model\ModelManager')
           ->getInstance('Application\Model\Setting');

        return $settingsModel->getSetting($settingName,
                ($language ? $language : Localization::getCurrentLocalization()['language']));
    }
}
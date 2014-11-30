<?php
namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Application\Service\ApplicationSetting as SettingService;

class ApplicationSetting extends AbstractPlugin
{
    /**
     * Get a setting
     *
     * @param string $setting
     * @param string $language
     * @return string
     */
    public function __invoke($setting, $language = null)
    {
        return SettingService::getSetting($setting, $language);
    }
}
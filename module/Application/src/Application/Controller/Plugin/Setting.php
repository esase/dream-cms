<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Users\Service\Service as UsersService;

/**
 * Controller plugin for getting a setting.
 */
class Setting extends AbstractPlugin
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
        return UsersService::getSetting($setting, $language);
    }
}
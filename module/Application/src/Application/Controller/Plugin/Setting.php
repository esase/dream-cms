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
     * @return string
     */
    public function __invoke($setting)
    {
        return UsersService::getSetting($setting);
    }
}
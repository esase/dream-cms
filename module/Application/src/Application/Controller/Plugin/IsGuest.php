<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Users\Service\Service as UsersService;

/**
 * Controller plugin for checking the user permission.
 */
class IsGuest extends AbstractPlugin
{
    /**
     * Is guest.
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UsersService::isGuest();
    }
}
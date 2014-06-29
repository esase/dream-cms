<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use User\Service\Service as UserService;

/**
 * Controller plugin for checking the user permission.
 */
class IsGuest extends AbstractPlugin
{
    /**
     * Is guest or not?.
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UserService::isGuest();
    }
}
<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Users\Service\Service as UsersService;

/**
 * Controller plugin for checking the user permission.
 */
class CheckPermission extends AbstractPlugin
{
    /**
     * Check current user permission.
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @return boolean
     */
    public function __invoke($resource, $increaseActions = true)
    {
        return UsersService::checkPermission($resource, $increaseActions);
    }
}
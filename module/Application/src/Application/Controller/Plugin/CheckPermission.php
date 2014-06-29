<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use User\Service\Service as UserService;

/**
 * Controller plugin for checking a user permission.
 */
class CheckPermission extends AbstractPlugin
{
    /**
     * Check the current user permission.
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @param boolean $showAccessDenied
     * @return boolean
     */
    public function __invoke($resource = null, $increaseActions = true, $showAccessDenied = true)
    {
        // get ACL resource name
        $resource = !$resource
            ? $this->getController()->params('controller') . ' ' . $this->getController()->params('action')
            : $resource;

        // check the permission
        if (false === ($result = UserService::checkPermission($resource,
                $increaseActions)) && $showAccessDenied) {

            // redirect to access a forbidden page
            $this->getController()->showErrorPage();
        }

        return $result;
    }
}
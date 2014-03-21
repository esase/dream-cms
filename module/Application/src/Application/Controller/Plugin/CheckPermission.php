<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use User\Service\Service as UserService;
use Zend\Http\Response;

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
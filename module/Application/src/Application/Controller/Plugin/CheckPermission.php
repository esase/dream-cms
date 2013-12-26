<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Users\Service\Service as UsersService;
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
        if (false === ($result = UsersService::checkPermission($resource,
                $increaseActions)) && $showAccessDenied) {

            // redirect to access denied page
            $this->getController()->getResponse()->setStatusCode(Response::STATUS_CODE_302);
            $this->getController()->plugin('Redirect')->toRoute('application', array(
                'controller' => 'error',
                'action' => 'forbidden'
            ));
        }

        return $result;
    }
}
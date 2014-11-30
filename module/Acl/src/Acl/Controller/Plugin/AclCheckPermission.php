<?php
namespace Acl\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Acl\Service\Acl as AclService;
use Zend\Http\Response;

/**
 * Controller plugin for checking users permission.
 */
class AclCheckPermission extends AbstractPlugin
{
    /**
     * Check current user's permission.
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @param boolean $showAccessDenied
     * @return boolean
     */
    public function __invoke($resource = null, $increaseActions = true, $showAccessDenied = true)
    {
        // get an ACL resource name
        $resource = !$resource
            ? $this->getController()->params('controller') . ' ' . $this->getController()->params('action')
            : $resource;

        // check the permission
        if (false === ($result = 
                AclService::checkPermission($resource, $increaseActions)) && $showAccessDenied) {

            // redirect to access a forbidden page
            $this->getController()->showErrorPage();
        }

        return $result;
    }
}
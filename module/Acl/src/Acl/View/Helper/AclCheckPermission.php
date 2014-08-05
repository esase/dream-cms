<?php
namespace Acl\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

class AclCheckPermission extends AbstractHelper
{
    /**
     * Check permission
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @return boolean
     */
    public function __invoke($resource, $increaseActions = false)
    {
        return UserService::checkPermission($resource, $increaseActions);
    }
}

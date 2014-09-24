<?php
namespace Acl\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Acl\Service\Acl as AclService;

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
        return AclService::checkPermission($resource, $increaseActions);
    }
}
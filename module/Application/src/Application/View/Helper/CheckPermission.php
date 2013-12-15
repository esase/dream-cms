<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Users\Service\Service as UsersService;

class CheckPermission extends AbstractHelper
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
        return UsersService::checkPermission($resource, $increaseActions);
    }
}

<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Users\Service\Service as UsersService;

class UserIdentity extends AbstractHelper
{
    /**
     * User identity
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UsersService::getCurrentUserIdentity();
    }
}

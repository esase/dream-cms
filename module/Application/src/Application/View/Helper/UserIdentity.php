<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

class UserIdentity extends AbstractHelper
{
    /**
     * User identity
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UserService::getCurrentUserIdentity();
    }
}

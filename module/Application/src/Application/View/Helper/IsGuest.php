<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

class IsGuest extends AbstractHelper
{
    /**
     * Is guest
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UserService::isGuest();
    }
}

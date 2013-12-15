<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Users\Service\Service as UsersService;

class IsGuest extends AbstractHelper
{
    /**
     * Is guest
     *
     * @return boolean
     */
    public function __invoke()
    {
        return UsersService::isGuest();
    }
}

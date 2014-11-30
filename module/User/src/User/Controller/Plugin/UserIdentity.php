<?php
namespace User\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use User\Service\UserIdentity as UserIdentityService;

class UserIdentity extends AbstractPlugin
{
    /**
     * Get current user identity 
     *
     * @return object
     */
    public function __invoke()
    {
        return UserIdentityService::getCurrentUserIdentity();
    }
}
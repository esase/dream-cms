<?php
namespace Application\Controller;

use Zend\EventManager\EventManagerInterface;
use User\Service\Service as UserService;

abstract class AbstractAdministrationController extends AbstractBaseController
{
    /**
     * Layout name
     */
    protected $layout = 'layout/administration';

    /**
     * Set event manager
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;

        // execute before executing action logic
        $events->attach('dispatch', function ($e) use ($controller) {
            // check permission
            if (!UserService::checkPermission($controller->
                    params('controller') . ' ' . $controller->params('action'), false)) {

                return $controller->showErrorPage();
            }

            // set an admin layout
            $controller->layout($this->layout);
        }, 100);
    }
}
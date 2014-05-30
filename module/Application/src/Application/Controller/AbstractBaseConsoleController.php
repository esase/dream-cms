<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\EventManager\EventManagerInterface;
use Zend\Console\Request as ConsoleRequest;

abstract class AbstractBaseConsoleController extends AbstractActionController
{
    /**
     * Set event manager
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;

        // execute before executing action logic
        $events->attach('dispatch', function ($e) use ($controller) {
            $request = $this->getRequest();
            // Make sure that we are running in a console and the user has not tricked our
            // application into running this action from a public web server.
            if (!$request instanceof ConsoleRequest) {
                return $this->notFoundAction();
            }
        }, 100); 
    }
}

<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Users\Controller;

use Application\Controller\AbstractBaseController;

abstract class BaseController extends AbstractBaseController
{
    /**
     * Auth service
     * @var object  
     */
    protected $authService;

    /**
     * Get auth service
     */
    protected function getAuthService()
    {
        if (!$this->authService) {
            $this->authService = $this->getServiceLocator()->get('Application\AuthService');
        }

        return $this->authService;
    }
}

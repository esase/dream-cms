<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Type;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Service\Service as ApplicationService;

abstract class AbstractType implements PaymentTypeInterface
{
    /**
     * Controller
     * @var object
     */
    protected $controller;

    /**
     * Class constructor
     *
     * @param object $serviceManager
     */
    public function __construct(AbstractActionController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Get success url
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->controller->url()->fromRoute('application', array(
            'controller' => 'payments',
            'action' => 'success'
        ), array('force_canonical' => true));
    }

    /**
     * Get error url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->controller->url()->fromRoute('application', array(
            'controller' => 'payments',
            'action' => 'error'
        ), array('force_canonical' => true));
    }
}
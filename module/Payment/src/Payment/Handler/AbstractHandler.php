<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Handler;

use Zend\ServiceManager\ServiceManager;

abstract class AbstractHandler implements InterfaceHandler
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Class constructor
     *
     * @param object $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }
}
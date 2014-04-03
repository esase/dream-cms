<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class PaymentController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\Payment');
        }

        return $this->model;
    }

    /**
     * Add to basket
     */
    public function addToBasketAction()
    {
        $objectId = $this->params()->fromPost('objectId', -1);
        $module   = $this->params()->fromPost('module');

        $view = new ViewModel(array(
            'basketId' => $module//$this->getModel()->getBasketId()
        ));

        $view->setTerminal(true);
        return $view;
    }
}
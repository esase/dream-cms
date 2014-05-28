<?php

namespace Payment;

use Zend\Mvc\MvcEvent;
use Payment\Event\Event as PaymentEvent;
use Zend\ModuleManager\ModuleManager;
use User\Model\Base as UserBaseModel;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleUsageProviderInterface
{
    /**
     * Init
     *
     * @param object $moduleManager
     */
    function init(ModuleManager $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');
    }

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {           
        $model = $mvcEvent->getApplication()->getServiceManager()
            ->get('Application\Model\ModelManager')
            ->getInstance('Payment\Model\Base');

        // update a user transactions info
        $eventManager = PaymentEvent::getEventManager();

        //TODO: Here also need to attach modules change states events. And recalculate transactions amounts.

        // init edit and update events for payment modules
        foreach ($model->getPaymentModules() as $moduleInfo) {
            // get the payment handler
            $paymentHandler = $mvcEvent->getApplication()->getServiceManager()
                ->get('Payment\Handler\HandlerManager')
                ->getInstance($moduleInfo['handler']);

            // update items
            $eventManager->attach($moduleInfo['update_event'],
                    function ($e) use ($model, $moduleInfo, $paymentHandler) {

                if (true === ($result = $model->updateItemsInfo($e->getParam('object_id'), $moduleInfo, $paymentHandler))) {
                    // fire edit items event
                    PaymentEvent::fireEditItemsEvent($e->getParam('object_id'), $moduleInfo['module']);
                }
            });

            // mark items as deleted
            $eventManager->attach($moduleInfo['delete_event'], function ($e) use ($model, $moduleInfo) {
                if (true === ($result = $model->markItemsDeleted($e->getParam('object_id'), $moduleInfo['module']))) {
                    // fire the mark deleted items event
                    PaymentEvent::fireMarkDeletedItemsEvent($e->getParam('object_id'), $moduleInfo['module']);
                }
            });
        }
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Payment\Type\PaymentTypeManager' => function($serviceManager)
                {
                    $basePaymentModel = $serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Payment\Model\Base');

                    return new Type\PaymentTypeManager($serviceManager->
                            get('request'), $basePaymentModel, $serviceManager->get('viewhelpermanager')->get('url'));
                },
                'Payment\Handler\HandlerManager' => function($serviceManager)
                {
                    return new Handler\HandlerManager($serviceManager);
                },
            )
        );
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return array(
            'invokables' => array(
                'costFormat' => 'Payment\View\Helper\CostFormat',
                'processCost' => 'Payment\View\Helper\ProcessCost',
                'currency' => 'Payment\View\Helper\Currency',
                'paymentItemStatus' => 'Payment\View\Helper\PaymentItemStatus',
                'paymentItemLink' => 'Payment\View\Helper\PaymentItemlink',
                'shoppingCart' => 'Payment\View\Helper\ShoppingCart'
            ),
            'factories' => array(
                'paymentItemExtraOptions' =>  function()
                {
                    // get the payment handler manager
                    $paymentHandlerManager = $this->serviceManager
                        ->get('Payment\Handler\HandlerManager');

                    return new \Payment\View\Helper\PaymentItemExtraOptions($paymentHandlerManager);
                },
            )
        );
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get console usage info
     *
     * @param object $console
     * @return array
     */
    public function getConsoleUsage(Console $console)
    {
        return array(
            // describe available commands
            'payment clean expired items [--verbose|-v]' => 'Clean expired shopping cart and items and expired not paid transactions',
            // describe expected parameters
            array('--verbose|-v', '(optional) turn on verbose mode'),
        );
    }
}
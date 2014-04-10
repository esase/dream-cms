<?php

namespace Payment;

use Zend\Mvc\MvcEvent;
use User\Event\Event as UserEvent;
use Payment\Event\Event as PaymentEvent;
use Zend\ModuleManager\ModuleManager;

class Module
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
        $eventManager->attach(UserEvent::EDIT, function ($e) use ($model) {
            $model->updateUserTransactionsInfo($e->getParam('object_id'));
        });

        // init edit and update events for payment modules
        foreach ($model->getPaymentModules() as $moduleInfo) {
            // update items
            $eventManager->attach($moduleInfo['update_event'], function ($e) use ($model, $moduleInfo) {
                $model->updateItemsInfo($e->getParam('object_id'), $moduleInfo);
            });

            // mark items as deleted
            $eventManager->attach($moduleInfo['delete_event'], function ($e) use ($model, $moduleInfo) {
                $model->markItemsDeleted($e->getParam('object_id'), $moduleInfo['module']);
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
        );
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return array(
            'invokables' => array(
            ),
            'factories' => array(
                'shoppingCart' => function()
                {
                    $payment = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Payment\Model\Base');

                    return new \Payment\View\Helper\ShoppingCart($payment->getAllShoppingCartItems());
                },
                'exchangeRates' => function()
                {
                    $payment = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Payment\Model\Base');

                    return new \Payment\View\Helper\ExchangeRates($payment->getExchangeRates());
                }
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
}
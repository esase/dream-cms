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
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;
use Payment\Event\Event as PaymentEvent;
use User\Service\Service as UserService;
use Payment\Service\Service as PaymentService;

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
     * Add to shopping cart
     *
     * @param array $itemInfo
     *      integer object_id - required
     *      integer module - required
     *      string title - required
     *      string slug - optional
     *      float cost - required
     *      float discount - optional
     *      integer count - required
     * @param object $paymentObject
     * @return boolean
     */
    protected function addToShoppingCart($itemInfo, PaymentInterfaceHandler $paymentObject)
    {
        $result = $this->getModel()->addToShoppingCart($itemInfo);

        if (is_numeric($result)) {
            // clear the item's discount
            if ($itemInfo['discount']) {
                $paymentObject->clearDiscount($itemInfo['object_id'], $itemInfo['discount']);
            }

            // fire the event
            $eventDesc = UserService::isGuest()
                ? 'Event - Item added to shopping cart by guest'
                : 'Event - Item added to shopping cart by user';

            $eventDescParams = UserService::isGuest()
                ? array($result)
                : array(UserService::getCurrentUserIdentity()->nick_name, $result);

            PaymentEvent::fireEvent(PaymentEvent::ADD_ITEM_TO_SHOPPING_CART,
                    $result, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

            return true;
        }

        return false;
    }

    /**
     * Deactivate current discount coupon
     */
    public function deactivateDiscountCouponAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null != ($couponCode = PaymentService::getCurrentDiscount())) {
                PaymentService::setCurrentDiscount(array());

                // fire the event
                $eventDesc = UserService::isGuest()
                    ? 'Event - Discount coupon deactivated by guest'
                    : 'Event - Discount coupon deactivated by user';

                $eventDescParams = UserService::isGuest()
                    ? array($couponCode['slug'])
                    : array(UserService::getCurrentUserIdentity()->nick_name, $couponCode['slug']);

                PaymentEvent::fireEvent(PaymentEvent::DEACTIVATE_DISCOUNT_COUPON,
                        $couponCode['slug'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                $this->flashMessenger()
                    ->setNamespace('success')
                    ->addMessage($this->getTranslator()->translate('The coupon code has been deactivated'));
            }
        }

        return  $this->getResponse();
    }

    /**
     * Delete selected items from shopping cart
     */
    public function deleteShoppingCartItemsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($itemsIds = $request->getPost('items', null))) {
                // event's description
                $eventDesc = UserService::isGuest()
                    ? 'Event - Item deleted from shopping cart by guest'
                    : 'Event - Item deleted from shopping cart by user';

                // delete selected items
                foreach ($itemsIds as $itemId) {
                    // get an item info
                    if (null == ($itemInfo = $this->getModel()->getShoppingCartItemInfo($itemId))) { 
                        continue;
                    }

                    // delete the item
                    if (true !== ($deleteResult = $this->getModel()->deleteFromShoppingCart($itemId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Error occurred'));

                        break;
                    }

                    // return a discount back
                    if ($itemInfo['discount']) {
                        $this->getModel()
                            ->getPaymentHandlerInstance($itemInfo['handler'])
                            ->returnBackDiscount($itemId, $itemInfo['discount']);
                    }

                    $eventDescParams = UserService::isGuest()
                        ? array($itemId)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $itemId);

                    PaymentEvent::fireEvent(PaymentEvent::DELETE_ITEM_FROM_SHOPPING_CART,
                        $itemId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected items have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('payment', 'shopping-cart', array(), true);
    }

    /**
     * Activate a discount coupon
     */
    public function activateDiscountCouponAction()
    {
        $refreshPage = false;

        $discountForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\DiscountForm')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        if ($request->isPost()) {
            $discountForm->getForm()->setData($request->getPost(), false);

            if ($discountForm->getForm()->isValid()) {
                // activate a discount coupon
                $couponCode = $discountForm->getForm()->getData()['coupon'];

                // save the activated discount in sessions
                PaymentService::setCurrentDiscount($this->getModel()->getCouponInfo($couponCode, 'slug'));

                // fire the event
                $eventDesc = UserService::isGuest()
                    ? 'Event - Discount coupon activated by guest'
                    : 'Event - Discount coupon activated by user';

                $eventDescParams = UserService::isGuest()
                    ? array($couponCode)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $couponCode);

                PaymentEvent::fireEvent(PaymentEvent::ACTIVATE_DISCOUNT_COUPON,
                        $couponCode, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('The coupon code has been activated'));

                $refreshPage = true;
            }
        }

        $view = new ViewModel(array(
            'discountForm' => $discountForm->getForm(),
            'refreshPage' => $refreshPage
        ));

        $view->setTerminal(true);
        return $view;
    }

    /**
     * Add to shopping cart
     */
    public function addToShoppingCartAction()
    {
        $objectId = $this->params()->fromPost('object_id', -1);
        $module   = $this->params()->fromPost('module');
        $count    = (int) $this->params()->fromPost('count', 0);

        $shoppingCartForm  = $message = null;
        $updateShopingCart = false;

        // get a module info 
        if (null == ($moduleInfo = $this->getModel()->getModuleInfo($module))) {
            $message = sprintf($this->
                getTranslator()->translate('Received module not found'), $module);
        }
        else {
            // create a payment handler class instance
            $object = $this->getModel()->getPaymentHandlerInstance($moduleInfo['handler']);

            // check an item existing in shopping cart
            if (true === ($result = $this->getModel()->inShoppingCart($objectId, $moduleInfo['id']))) {
                $message = $this->getTranslator()->translate('Item already added into your shopping cart'); 
            }
            else {
                // get the item info
                if (null == $objectInfo = $object->getItemInfo($objectId)) {
                    $message = $this->getTranslator()->translate('Sorry but the item not found or not activated');    
                }
                else {
                    // item's count is not available
                    if ($moduleInfo['countable'] && $objectInfo['count'] <= 0) {
                        $message = $this->getTranslator()->translate('Item not available');
                    }
                    else {
                        // show an additional shopping cart form
                        if ($objectInfo['discount'] || is_array($objectInfo['cost']) ||
                                ($moduleInfo['countable'] && ($count <= 0 || $count > $objectInfo['count']))) {
    
                            // get the form instance
                            $shoppingCartForm = $this->getServiceLocator()
                                ->get('Application\Form\FormManager')
                                ->getInstance('Payment\Form\ShoppingCart')
                                ->hideCountField(!$moduleInfo['countable'])
                                ->setDiscount($objectInfo['discount'])
                                ->setTariffs((is_array($objectInfo['cost']) ? $objectInfo['cost'] : array()))
                                ->setCountLimit(($moduleInfo['countable'] ? $objectInfo['count'] : 0));

                            $request = $this->getRequest();
                            $shoppingCartForm->getForm()->setData($request->getPost(), false);
        
                            // validate the form
                            if ($request->isPost() && null !== $this->params()->fromPost('validate', null)) {
                                if ($shoppingCartForm->getForm()->isValid()) {
                                    $formData = $shoppingCartForm->getForm()->getData();
                                    $itemInfo = array(
                                        'object_id' => $objectId,
                                        'module'    => $moduleInfo['id'],
                                        'title'     => $objectInfo['title'],
                                        'slug'      => $objectInfo['slug'],
                                        'cost'      => !empty($formData['cost']) ? $formData['cost'] : $objectInfo['cost'],
                                        'discount'  => !empty($formData['discount']) ? $objectInfo['discount'] : 0,
                                        'count'     => $moduleInfo['countable'] ? $count : 1
                                    );
    
                                    // add the item into the shopping cart
                                    $shoppingCartForm = null;
                                    if (true === ($result = $this->addToShoppingCart($itemInfo, $object))) {
                                        $updateShopingCart = true;
                                        $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                                    }
                                    else {
                                        $message = $this->getTranslator()->translate('Error occurred');
                                    }
                                }
                            }
                        }
                        else {
                            $itemInfo = array(
                                'object_id' => $objectId,
                                'module'    => $moduleInfo['id'],
                                'title'     => $objectInfo['title'],
                                'slug'      => $objectInfo['slug'],
                                'cost'      => $objectInfo['cost'],
                                'discount'  => 0,
                                'count'     => $moduleInfo['countable'] ? $count : 1
                            );
    
                            if (true === ($result = $this->addToShoppingCart($itemInfo, $object))) {
                                $updateShopingCart = true;
                                $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                            }
                            else {
                                $message = $this->getTranslator()->translate('Error occurred');
                            }
                        }
                    }
                }
            }
        }

        $view = new ViewModel(array(
            'updateShopingCart' => $updateShopingCart,
            'shoppingCartForm' => $shoppingCartForm ? $shoppingCartForm->getForm() : null,
            'message' => $message
        ));

        $view->setTerminal(true);
        return $view;
    }

    /**
     * Change currency
     */
    public function changeCurrencyAction()
    {
        $this->getModel()->setShoppingCartCurrency($this->params()->fromPost('currency'));
        return  $this->getResponse();
    }

    /**
     * Update shopping cart
     */
    public function updateShoppingCartAction()
    {
        $view = new ViewModel(array());
        $view->setTerminal(true);

        return $view;
    }

    /**
     * Clean shopping cart
     */
    public function cleanShoppingCartAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            // get all shopping cart items
            if (null != ($items = $this->getModel()->getAllShoppingCartItems(false))) {
                // event's description
                $eventDesc = UserService::isGuest()
                    ? 'Event - Item deleted from shopping cart by guest'
                    : 'Event - Item deleted from shopping cart by user';

                // delete all items
                foreach ($items as $itemInfo) {
                    if (true !== ($deleteResult = $this->getModel()->deleteFromShoppingCart($itemInfo['id']))) {
                        break;
                    }

                    // return a discount back
                    if ($itemInfo['discount']) {
                        $this->getModel()
                            ->getPaymentHandlerInstance($itemInfo['handler'])
                            ->returnBackDiscount($itemInfo['id'], $itemInfo['discount']);
                    }

                    $eventDescParams = UserService::isGuest()
                        ? array($itemInfo['id'])
                        : array(UserService::getCurrentUserIdentity()->nick_name, $itemInfo['id']);

                    PaymentEvent::fireEvent(PaymentEvent::DELETE_ITEM_FROM_SHOPPING_CART,
                        $itemInfo['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }
            }
        }

        $view = new ViewModel(array());
        $view->setTerminal(true);

        return $view;
    }

    /**
     * Shopping cart
     */
    public function shoppingCartAction()
    {
        // get data
        $paginator = $this->getModel()->
                getShoppingCartItems($this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel(array(
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }
}
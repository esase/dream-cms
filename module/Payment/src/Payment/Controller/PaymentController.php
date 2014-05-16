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
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;
use Payment\Event\Event as PaymentEvent;
use User\Service\Service as UserService;
use Payment\Service\Service as PaymentService;
use Payment\Model\Payment as PaymentModel;
use Application\Utility\EmailNotification;
use User\Model\Base as UserBaseModel;

class PaymentController extends PaymentBaseController
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
     * Default action
     */
    public function indexAction()
    {
        // redirect to shopping cart action
        return $this->redirectTo('payments', 'shopping-cart');
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
     * @param object $paymentHandler
     * @return boolean
     */
    protected function addToShoppingCart($itemInfo, PaymentInterfaceHandler $paymentHandler)
    {
        $result = $this->getModel()->addToShoppingCart($itemInfo);

        if (is_numeric($result)) {
            // clear the item's discount
            if ($itemInfo['discount']) {
                $paymentHandler->clearDiscount($itemInfo['object_id'], $itemInfo['discount']);
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
            if (null != ($discountCouponInfo = PaymentService::getDiscountCouponInfo())) {
                PaymentService::setDiscountCouponId(null);

                // fire the event
                $eventDesc = UserService::isGuest()
                    ? 'Event - Discount coupon deactivated by guest'
                    : 'Event - Discount coupon deactivated by user';

                $eventDescParams = UserService::isGuest()
                    ? array($discountCouponInfo['slug'])
                    : array(UserService::getCurrentUserIdentity()->nick_name, $discountCouponInfo['slug']);

                PaymentEvent::fireEvent(PaymentEvent::DEACTIVATE_DISCOUNT_COUPON,
                        $discountCouponInfo['slug'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                $this->flashMessenger()
                    ->setNamespace('success')
                    ->addMessage($this->getTranslator()->translate('The coupon code has been deactivated'));
            }
        }

        return  $this->getResponse();
    }

    /**
     * Edit shopping cart's item
     */
    public function editShoppingCartItemAction()
    {
        // get an item's info
        if (null == ($itemInfo =
                $this->getModel()->getShoppingCartItemInfo($this->getSlug(), true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the payment handler
        $paymentHandler = $this->getServiceLocator()
            ->get('Payment\Handler\HandlerManager')
            ->getInstance($itemInfo['handler']);

        // get the item's additional info
        $extraItemInfo = $paymentHandler->getItemInfo($itemInfo['object_id']);

        // extra checks
        if ($itemInfo['countable'] == PaymentModel::MODULE_COUNTABLE
                || $itemInfo['multi_costs'] == PaymentModel::MODULE_MULTI_COSTS
                || ($itemInfo['module_extra_options'] == PaymentModel::MODULE_EXTRA_OPTIONS && !empty($extraItemInfo['extra_options']))
                || $itemInfo['discount']
                || $paymentHandler->getDiscount($itemInfo['object_id'])) {

            $refreshPage = false;



            // get a form instance
            $shoppingCartForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Payment\Form\ShoppingCart')
                ->hideCountField($itemInfo['countable'] != PaymentModel::MODULE_COUNTABLE)
                ->setDiscount(($itemInfo['discount'] ? $itemInfo['discount'] : $extraItemInfo['discount']))
                ->setCountLimit((PaymentModel::MODULE_COUNTABLE == $itemInfo['countable'] ? $extraItemInfo['count'] : 0));

            if (PaymentModel::MODULE_MULTI_COSTS == $itemInfo['multi_costs']) {
                $shoppingCartForm->setTariffs($extraItemInfo['cost']);
            }

            // fill the form with default values
            $defaultFormValues = array_merge($itemInfo, array(
                'discount' => $itemInfo['discount'] ? 1 : 0
            ));

            // add extra options in the form
            if ($itemInfo['module_extra_options'] ==
                        PaymentModel::MODULE_EXTRA_OPTIONS && !empty($extraItemInfo['extra_options'])) {

                $shoppingCartForm->setExtraOptions($extraItemInfo['extra_options']);

                // fill a default value
                if ($itemInfo['extra_options']) {
                    $defaultFormValues = array_merge($defaultFormValues, unserialize($itemInfo['extra_options']));
                }
            }

            $shoppingCartForm->getForm()->setData($defaultFormValues);

            $request = $this->getRequest();
            $shoppingCartForm->getForm()->setData($request->getPost(), false);

            // validate the form
            if ($request->isPost()) {
                if ($shoppingCartForm->getForm()->isValid()) {
                    // get the form's data
                    $formData = $shoppingCartForm->getForm()->getData();

                    // get the item's extra options
                    $extraOptions = $shoppingCartForm->getExtraOptions($formData);

                    $newItemInfo = array(
                        'cost' => !empty($formData['cost']) ? $formData['cost'] : $itemInfo['cost'],
                        'count' => PaymentModel::MODULE_COUNTABLE == $itemInfo['countable'] ? $formData['count'] : 1,
                        'discount'  => !empty($formData['discount'])
                            ? ($itemInfo['discount'] ? $itemInfo['discount'] : $extraItemInfo['discount'])
                            : 0,
                        'extra_options' => $extraOptions ? serialize($extraOptions) : '',
                    );

                    // update the item into the shopping cart
                    if (true === ($result = $this->getModel()->updateShoppingCartItem($itemInfo['id'], $newItemInfo))) {
                        $refreshPage = true;

                        // return a discount back
                        if ($itemInfo['discount'] && empty($formData['discount'])) {
                            // get the payment handler
                            $this->getServiceLocator()
                                ->get('Payment\Handler\HandlerManager')
                                ->getInstance($itemInfo['handler'])
                                ->returnBackDiscount($itemInfo['object_id'], $itemInfo['discount']);
                        }

                        // fire the event
                        $eventDesc = UserService::isGuest()
                            ? 'Event - Item edited into the shopping cart by guest'
                            : 'Event - Item edited into the shopping cart by user';

                        $eventDescParams = UserService::isGuest()
                            ? array($itemInfo['id'])
                            : array(UserService::getCurrentUserIdentity()->nick_name, $itemInfo['id']);

                        PaymentEvent::fireEvent(PaymentEvent::EDIT_ITEM_INTO_SHOPPING_CART,
                                $itemInfo['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Item has been edited'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Error occurred'));
                    }
                }
            }

            $view = new ViewModel(array(
                'refreshPage' => $refreshPage,
                'id' => $itemInfo['id'],
                'shoppingCartForm' => $shoppingCartForm->getForm(),
            ));

            $view->setTerminal(true);
            return $view;
        }
        else {
            return $this->createHttpNotFoundModel($this->getResponse());
        }
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
                    if (null == ($itemInfo = $this->getModel()->getShoppingCartItemInfo($itemId, false, false))) { 
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
                        // get the payment handler
                        $this->getServiceLocator()
                            ->get('Payment\Handler\HandlerManager')
                            ->getInstance($itemInfo['handler'])
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

                // save the activated discount coupon's ID in sessions
                PaymentService::setDiscountCouponId($this->getModel()->getCouponInfo($couponCode, 'slug')['id']);

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
            // get the payment handler
            $paymentHandler = $this->getServiceLocator()
                ->get('Payment\Handler\HandlerManager')
                ->getInstance($moduleInfo['handler']);

            // check an item existing in shopping cart
            if (true === ($result = $this->getModel()->inShoppingCart($objectId, $moduleInfo['id']))) {
                $message = $this->getTranslator()->translate('Item already added into your shopping cart'); 
            }
            else {
                // get the item info
                if (null == $objectInfo = $paymentHandler->getItemInfo($objectId)) {
                    $message = $this->getTranslator()->translate('Sorry but the item not found or not activated');    
                }
                else {
                    // item's count is not available
                    if (PaymentModel::MODULE_COUNTABLE == $moduleInfo['countable'] && $objectInfo['count'] <= 0) {
                        $message = $this->getTranslator()->translate('Item is not available');
                    }
                    else {
                        // show an additional shopping cart form
                        if ($objectInfo['discount']
                                ||  PaymentModel::MODULE_MULTI_COSTS == $moduleInfo['multi_costs']
                                || (PaymentModel::MODULE_EXTRA_OPTIONS == $moduleInfo['extra_options'] && !empty($objectInfo['extra_options']))
                                || (PaymentModel::MODULE_COUNTABLE == $moduleInfo['countable'] && ($count <= 0 || $count > $objectInfo['count']))) {

                            // get the form instance
                            $shoppingCartForm = $this->getServiceLocator()
                                ->get('Application\Form\FormManager')
                                ->getInstance('Payment\Form\ShoppingCart')
                                ->hideCountField($moduleInfo['countable'] != PaymentModel::MODULE_COUNTABLE)
                                ->setDiscount($objectInfo['discount'])
                                ->setCountLimit((PaymentModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $objectInfo['count'] : 0));

                            if (PaymentModel::MODULE_EXTRA_OPTIONS ==
                                        $moduleInfo['extra_options'] && !empty($objectInfo['extra_options'])) {

                                $shoppingCartForm->setExtraOptions($objectInfo['extra_options']);
                            }

                            if (PaymentModel::MODULE_MULTI_COSTS == $moduleInfo['multi_costs']) {
                                $shoppingCartForm->setTariffs($objectInfo['cost']);
                            }

                            $request = $this->getRequest();
                            $shoppingCartForm->getForm()->setData($request->getPost(), false);
        
                            // validate the form
                            if ($request->isPost() && null !== $this->params()->fromPost('validate', null)) {
                                if ($shoppingCartForm->getForm()->isValid()) {
                                    $formData = $shoppingCartForm->getForm()->getData();
                                    
                                    // get the item's extra options
                                    $extraOptions = $shoppingCartForm->getExtraOptions($formData);

                                    $itemInfo = array(
                                        'object_id'     => $objectId,
                                        'module'        => $moduleInfo['id'],
                                        'title'         => $objectInfo['title'],
                                        'slug'          => $objectInfo['slug'],
                                        'cost'          => !empty($formData['cost']) ? $formData['cost'] : $objectInfo['cost'],
                                        'discount'      => !empty($formData['discount']) ? $objectInfo['discount'] : 0,
                                        'count'         => PaymentModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $count : 1,
                                        'extra_options' => $extraOptions ? serialize($extraOptions) : '',
                                    );

                                    // add the item into the shopping cart
                                    $shoppingCartForm = null;
                                    if (true === ($result = $this->addToShoppingCart($itemInfo, $paymentHandler))) {
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
                                'object_id'     => $objectId,
                                'module'        => $moduleInfo['id'],
                                'title'         => $objectInfo['title'],
                                'slug'          => $objectInfo['slug'],
                                'cost'          => $objectInfo['cost'],
                                'discount'      => 0,
                                'count'         => PaymentModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $count : 1,
                                'extra_options' => ''
                            );
    
                            if (true === ($result = $this->addToShoppingCart($itemInfo, $paymentHandler))) {
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
     *
     * @param boolean $returnDiscount
     * @return boolean
     */
    protected function cleanShoppingCart($returnDiscount = true)
    {
        // get all shopping cart items
        if (null != ($items = $this->getModel()->getAllShoppingCartItems(false))) {
            // event's description
            $eventDesc = UserService::isGuest()
                ? 'Event - Item deleted from shopping cart by guest'
                : 'Event - Item deleted from shopping cart by user';

            // delete all items
            foreach ($items as $itemInfo) {
                if (true !== ($deleteResult = $this->getModel()->deleteFromShoppingCart($itemInfo['id']))) {
                    return false;
                }

                // return a discount back
                if ($returnDiscount && $itemInfo['discount']) {
                    // get the payment handler
                    $this->getServiceLocator()
                        ->get('Payment\Handler\HandlerManager')
                        ->getInstance($itemInfo['handler'])
                        ->returnBackDiscount($itemInfo['id'], $itemInfo['discount']);
                }

                $eventDescParams = UserService::isGuest()
                    ? array($itemInfo['id'])
                    : array(UserService::getCurrentUserIdentity()->nick_name, $itemInfo['id']);

                PaymentEvent::fireEvent(PaymentEvent::DELETE_ITEM_FROM_SHOPPING_CART,
                    $itemInfo['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
            }
        }

        return true;
    }

    /**
     * Clean shopping cart
     */
    public function cleanShoppingCartAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->cleanShoppingCart();
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

        // get the payment handler manager
        $paymentHandlerManager = $this->getServiceLocator()
            ->get('Payment\Handler\HandlerManager');

        return new ViewModel(array(
            'paymentHandlerManager' => $paymentHandlerManager,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }

    /**
     * Checkout
     */
    public function checkoutAction()
    {
        // get list of shopping cart's items
        $shoppingCartItems = PaymentService::getActiveShoppingCartItems();

        if (!count($shoppingCartItems)) {
            return $this->redirectTo('payments', 'shopping-cart');
        }

        // check additional params
        if (UserService::isGuest()) {
            foreach ($shoppingCartItems as $item) {
                if ($item['must_login'] == PaymentModel::MODULE_MUST_LOGIN) {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->
                                translate('Some of the items in your shopping cart requires you to be logged in'));

                    return $this->redirectTo('user', 'login',
                        array(), false, array('back' => $this->url()->fromRoute('application', array('controller' => 'payments', 'action' => 'checkout'))));
                }
            }
        }

        // get shopping cart items amount
        $amount = (float) paymentService::roundingCost(paymentService::getActiveShoppingCartItemsAmount(true));
        $transactionPaid = $amount > 0;

        // get payments types
        if (null == ($paymentsTypes = $this->getModel()->getPaymentsTypes()) && $transactionPaid) {
            $this->flashMessenger()
                ->setNamespace('error')
                ->addMessage($this->getTranslator()->
                        translate('No available payment types. Please try again later'));

            return $this->redirectTo('payments', 'shopping-cart');
        }

        // get a form instance
        $checkoutForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\Checkout')
            ->setPaymentsTypes($paymentsTypes)
            ->hidePaymentType(!$transactionPaid);

        // set default values
        if (!UserService::isGuest()) {
            $checkoutForm->getForm()->setData(array(
                'first_name' => UserService::getCurrentUserIdentity()->first_name,
                'last_name' => UserService::getCurrentUserIdentity()->last_name,
                'email' => UserService::getCurrentUserIdentity()->email,
                'phone' => UserService::getCurrentUserIdentity()->phone,
                'address' => UserService::getCurrentUserIdentity()->address,
            ), false);
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            // fill the form with received values
            $checkoutForm->getForm()->setData($request->getPost(), false);

            if ($checkoutForm->getForm()->isValid()) {
                // add a new transaction
                $formData = $checkoutForm->getForm()->getData();
                $result = $this->getModel()->addTransaction(UserService::getCurrentUserIdentity()->
                        user_id, $formData, $shoppingCartItems, $amount);

                if (is_numeric($result)) {
                    // fire the event
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Payment transaction added by guest'
                        : 'Event - Payment transaction added by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($result)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $result);
        
                    PaymentEvent::fireEvent(PaymentEvent::ADD_PAYMENT_TRANSACTION,
                            $result, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    // send an email notification about register the new transaction
                    if ((int) $this->getSetting('payment_transaction_add')) {
                        EmailNotification::sendNotification($this->getSetting('application_site_email'),
                            $this->getSetting('payment_transaction_add_title', UserService::getDefaultLocalization()['language']),
                            $this->getSetting('payment_transaction_add_message', UserService::getDefaultLocalization()['language']), array(
                                'find' => array(
                                    'FirstName',
                                    'LastName',
                                    'Email',
                                    'Id'
                                ),
                                'replace' => array(
                                    $formData['first_name'],
                                    $formData['last_name'],
                                    $formData['email'],
                                    $result
                                )
                            ));
                    }

                    // clean the shopping cart
                    $this->cleanShoppingCart(false);

                    // get created transaction info
                    $transactionInfo = $this->getModel()->getTransactionInfo($result);

                    // redirect to the buying page
                    if ($transactionPaid) {
                        return $this->redirectTo('payments', 'buy', array(
                            'slug'  => $transactionInfo['slug'],
                            'extra' => $transactionInfo['payment_name']
                        ));
                    }
                    else {
                        // activate the transaction and redirect to success page
                        if(true == ($result = $this->activateTransaction($transactionInfo))) {
                            return $this->redirectTo('payments', 'success');
                        }

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Transaction activation error'));

                        return $this->redirectTo('payments', 'shopping-cart');
                    }
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));

                    return $this->redirectTo('payments', 'checkout');
                }
            }
        }

        return new ViewModel(array(
            'checkoutForm' => $checkoutForm->getForm(),
        ));
    }

    /**
     * Success page
     */
    public function successAction()
    {
        return new ViewModel(array(
        ));
    }

    /**
     * Error page
     */
    public function errorAction()
    {
        return new ViewModel(array(
        ));
    }

    /**
     * Buy
     */
    public function buyAction()
    {
        // get a payments types list and the transaction info
        if (null == ($paymentsTypes = $this->getModel()->getPaymentsTypes(false, true))
                || null == ($transactionInfo = $this->getModel()->getTransactionInfo($this->getSlug(), true, 'slug'))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // check count of transaction's items
        if (!count($this->getModel()->getAllTransactionItems($transactionInfo['id']))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a default payment type
        if ($this->getExtra()) {
            if (!array_key_exists($this->getExtra(), $paymentsTypes)) {
                return $this->createHttpNotFoundModel($this->getResponse());    
            }

            $currentPayment = $this->getExtra();
        }
        else {
            // get a first payment type from the list
            $currentPayment = key($paymentsTypes);
        }

        // check the transaction's amount
        if (0 >= $transactionInfo['amount']) {
            // activate the transaction and redirect to success page
            if(true == ($result = $this->activateTransaction($transactionInfo))) {
                return $this->redirectTo('payments', 'success');
            }

            $this->flashMessenger()
                ->setNamespace('error')
                ->addMessage($this->getTranslator()->translate('Transaction activation error'));

            return $this->redirectTo('payments', 'shopping-cart');
        }

        // process payments
        $processedPayments = array();
        foreach ($paymentsTypes as $paymentName => $paymentInfo) {
            $processedPayments[$paymentName] = $paymentInfo['description'];
        }

        $paymentType = $this->getServiceLocator()
            ->get('Payment\Type\PaymentTypeManager')
            ->getInstance($paymentsTypes[$currentPayment]['handler']);

        $viewModel = new ViewModel(array(
            'transaction' => $transactionInfo,
            'payments' => $processedPayments,
            'current_payment' => $currentPayment,
            'amount' => $transactionInfo['amount'],
            'payment_options' => $paymentType->getPaymentOptions($transactionInfo['amount'], $transactionInfo),
            'payment_url' => $paymentType->getPaymentUrl()
        ));

        return $viewModel->setTemplate('payment/payment-type/' . $currentPayment);
    }

    /**
     * Activate transaction
     *
     * @param array $transactionInfo
     *      integer id
     *      string slug
     *      integer user_id
     *      string first_name
     *      string last_name
     *      string phone
     *      string address
     *      string email
     *      integer currency
     *      integer payment_type
     *      integer discount_cupon
     *      string currency_code
     *      string payment_name
     * @param integer $paymentTypeId
     * @param boolean $sendNotification
     * @return boolean
     */
    protected function activateTransaction(array $transactionInfo, $paymentTypeId = 0)
    {
        if (true === ($result = parent::activateTransaction($transactionInfo, $paymentTypeId))) {
            // fire the event
            PaymentEvent::fireEvent(PaymentEvent::ACTIVATE_PAYMENT_TRANSACTION, $transactionInfo['id'],
                    UserBaseModel::DEFAULT_SYSTEM_ID, 'Event - Payment transaction activated by the system', array($transactionInfo['id']));
        }

        return $result;
    }

    /**
     * Process action
     */
    public function processAction()
    {
        // get the payment's  info
        if (null == ($payment = $this->getModel()->getPaymentTypeInfo($this->getSlug()))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the payment type instance
        $paymentInstance = $this->getServiceLocator()
            ->get('Payment\Type\PaymentTypeManager')
            ->getInstance($payment['handler']);

        // validate payment
        if (false !== ($transactionInfo = $paymentInstance->validatePayment())) {
            if (true === ($result = $this->activateTransaction($transactionInfo, $payment['id']))) {
                // send an email notification about the paid transaction
                if ((int) $this->getSetting('payment_transaction_paid_users')) {
                    // get the user's info
                    $userInfo = !empty($transactionInfo['user_id'])
                        ? UserService::getUserInfo($transactionInfo['user_id'])
                        : array();

                    $notificationLanguage = !empty($userInfo['language'])
                        ? $userInfo['language'] // we should use the user's language
                        : UserService::getDefaultLocalization()['language'];

                    EmailNotification::sendNotification($transactionInfo['email'],
                            $this->getSetting('payment_transaction_paid_users_title', $notificationLanguage),
                            $this->getSetting('payment_transaction_paid_users_message', $notificationLanguage), array(
                                'find' => array(
                                    'Id',
                                    'PaymentType'
                                ),
                                'replace' => array(
                                    $transactionInfo['slug'],
                                    $this->getTranslator()->translate($payment['description'],
                                            'default', UserService::getLocalizations()[$notificationLanguage]['locale'])
                                )
                            ));
                }
            }
        }
        else {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return $this->getResponse();
    }

    /**
     * Transactions list 
     */
    public function listAction()
    {
        if ($this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $filters = array();

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\UserTransactionFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getUserTransactions(UserService::getCurrentUserIdentity()->
                user_id, $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'current_currency' => PaymentService::getPrimaryCurrency(),
            'payment_types' =>  $this->getModel()->getPaymentsTypes(false, true),
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }

    /**
     * View transaction's items
     */
    public function viewTransactionItemsAction()
    {
        if ($this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the transaction info
        if (null == ($transactionInfo = $this->getModel()->getTransactionInfo($this->
                getSlug(), false, 'id', false, UserService::getCurrentUserIdentity()->user_id))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get data
        $paginator = $this->getModel()->getTransactionItems($transactionInfo['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel(array(
            'transaction' => $transactionInfo,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }

    /**
     * Delete selected transactions
     */
    public function deleteTransactionsAction()
    {
        if ($this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($transactionsIds = $request->getPost('transactions', null))) {
                // event's description
                $eventDesc = 'Event - Payment transaction deleted by user';

                // delete selected transactions
                foreach ($transactionsIds as $transactionId) {
                    // delete the transaction
                    if (true !== ($deleteResult = $this->getModel()->
                            deleteTransaction($transactionId, UserService::getCurrentUserIdentity()->user_id))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // fire the event
                    $eventDescParams = UserService::isGuest()
                        ? array($transactionId)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $transactionId);

                    PaymentEvent::fireEvent(PaymentEvent::DELETE_PAYMENT_TRANSACTION,
                            $transactionId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected transactions have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('payments', 'list', array(), true);
    }
}
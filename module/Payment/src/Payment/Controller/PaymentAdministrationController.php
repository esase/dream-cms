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
use User\Service\Service as UserService;
use Payment\Event\Event as PaymentEvent;
use Payment\Model\Base as PaymentBaseModel;

class PaymentAdministrationController extends AbstractBaseController
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
                ->getInstance('Payment\Model\PaymentAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('payments-administration', 'list');
    }

    /**
     * Transactions list 
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }
    }

    /**
     * Delete selected currencies
     */
    public function deleteCurrenciesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($currenciesIds = $request->getPost('currencies', null))) {
                // event's description
                $eventDesc = UserService::isGuest()
                    ? 'Event - Payment currency deleted by guest'
                    : 'Event - Payment currency deleted by user';

                // delete selected currencies
                foreach ($currenciesIds as $currencyId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // delete the currency
                    if (true !== ($deleteResult = $this->getModel()->deleteCurrency($currencyId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // fire the event
                    $eventDescParams = UserService::isGuest()
                        ? array($currencyId)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $currencyId);

                    PaymentEvent::fireEvent(PaymentEvent::DELETE_PAYMENT_CURRENCY,
                            $currencyId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected currencies have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('payments-administration', 'currencies', array(), true);
    }

    /**
     * Edit exchange rates action
     */
    public function editExchangeRatesAction()
    {
        // get the currency info
        if (null == ($currency = $this->getModel()->getCurrencyInfo($this->
                getSlug(), true)) || null == ($exchangeRates = $this->getModel()->getExchangeRates())) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $exchangeRatesForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\ExchnageRate')
            ->setExchangeRates($exchangeRates);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $exchangeRatesForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($exchangeRatesForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the exchange rates
                if (true == ($result = $this->
                        getModel()->editExchangeRates($exchangeRates, $exchangeRatesForm->getForm()->getData()))) {

                    // fire the event
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Payment exchange rates edited by guest'
                        : 'Event - Payment exchange rates edited by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($currency['id'])
                        : array(UserService::getCurrentUserIdentity()->nick_name, $currency['id']);

                    PaymentEvent::fireEvent(PaymentEvent::EDIT_EXCHANGE_RATES,
                            $currency['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Exchange rates have been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'edit-exchange-rates', array(
                    'slug' => $currency['id']
                ));
            }
        }

        return new ViewModel(array(
            'currency' => $currency,
            'exchangeRatesForm' => $exchangeRatesForm->getForm()
        ));
    }

    /**
     * Edit a currency action
     */
    public function editCurrencyAction()
    {
        // get the currency info
        if (null == ($currency = $this->
                getModel()->getCurrencyInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $currencyForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\Currency')
            ->setModel($this->getModel())
            ->setCurrencyCodeId($currency['id'])
            ->enabledPrimaryCurrency($this->getModel()->
                    getCurrenciesCount() > 1 && $currency['primary_currency'] != PaymentBaseModel::PRIMARY_CURRENCY);

        $currencyForm->getForm()->setData($currency);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $currencyForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($currencyForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the currency
                if (true == ($result = $this->
                        getModel()->editCurrency($currency, $currencyForm->getForm()->getData()))) {

                    // fire the event
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Payment currency edited by guest'
                        : 'Event - Payment currency edited by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($currency['id'])
                        : array(UserService::getCurrentUserIdentity()->nick_name, $currency['id']);

                    PaymentEvent::fireEvent(PaymentEvent::EDIT_PAYMENT_CURRENCY,
                            $currency['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Currency has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'edit-currency', array(
                    'slug' => $currency['id']
                ));
            }
        }

        return new ViewModel(array(
            'currency' => $currency,
            'currencyForm' => $currencyForm->getForm()
        ));
    }

    /**
     * Add a currency
     */
    public function addCurrencyAction()
    {
        $currencyForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\Currency')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $currencyForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($currencyForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // add a new currency
                $result = $this->getModel()->addCurrency($currencyForm->getForm()->getData());

                if (is_numeric($result)) {
                    // fire the event
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Payment currency added by guest'
                        : 'Event - Payment currency added by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($result)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $result);

                    PaymentEvent::fireEvent(PaymentEvent::ADD_PAYMENT_CURRENCY,
                            $result, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Currency has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'add-currency');
            }
        }

        return new ViewModel(array(
            'currencyForm' => $currencyForm->getForm()
        ));
    }

    /**
     * Currencies list 
     */
    public function currenciesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        // get data
        $paginator = $this->getModel()->getCurrencies($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel(array(
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }
}
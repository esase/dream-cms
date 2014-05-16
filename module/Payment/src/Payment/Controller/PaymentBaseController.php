<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Controller;

use Application\Controller\AbstractBaseController;
use Application\Utility\EmailNotification;
use User\Service\Service as UserService;
use Payment\Model\Payment as PaymentModel;

abstract class PaymentBaseController extends AbstractBaseController
{
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
    protected function activateTransaction(array $transactionInfo, $paymentTypeId = 0, $sendNotification = true)
    {
        if (true === ($result =
                $this->getModel()->activateTransaction($transactionInfo['id'], 'id', $paymentTypeId))) {

            // mark as paid all transaction's items
            if (null != ($activeTransactionItems =
                    $this->getModel()->getAllTransactionItems($transactionInfo['id']))) {

                foreach ($activeTransactionItems as $itemInfo) {
                    // get the payment handler
                    $handler = $this->getServiceLocator()
                        ->get('Payment\Handler\HandlerManager')
                        ->getInstance($itemInfo['handler']);

                    // set an item as paid
                    $handler->setPaid($itemInfo['object_id'], $transactionInfo);

                    // decrease the item's count
                    if ($itemInfo['countable'] == PaymentModel::MODULE_COUNTABLE) {
                        $handler->decreaseCount($itemInfo['object_id'], $itemInfo['count']);
                    }
                }
            }

            // send an email notification about the paid transaction
            if ($sendNotification && (int) $this->getSetting('payment_transaction_paid')) {
                EmailNotification::sendNotification($this->getSetting('application_site_email'),
                    $this->getSetting('payment_transaction_paid_title', UserService::getDefaultLocalization()['language']),
                    $this->getSetting('payment_transaction_paid_message', UserService::getDefaultLocalization()['language']), array(
                        'find' => array(
                            'FirstName',
                            'LastName',
                            'Email',
                            'Id'
                        ),
                        'replace' => array(
                            $transactionInfo['first_name'],
                            $transactionInfo['last_name'],
                            $transactionInfo['email'],
                            $transactionInfo['id']
                        )
                    ));
            }

            return true;
        }

        return false;
    }
}
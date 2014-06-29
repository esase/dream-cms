<?php
namespace Payment\Controller;

use Application\Controller\AbstractBaseController;
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
     * @return boolean
     */
    protected function activateTransaction(array $transactionInfo, $paymentTypeId = 0)
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

            return true;
        }

        return false;
    }
}
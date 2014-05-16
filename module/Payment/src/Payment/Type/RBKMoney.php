<?php

namespace Payment\Type;

use Application\Service\Service as ApplicationService;
use Payment\Service\Service as PaymentService;

class RBKMoney extends AbstractType
{
    /**
     * Payment url
     * @var string
     */
    protected $paymentUrl = 'https://rbkmoney.ru/acceptpurchase.aspx';

    /**
     * Success status
     */
    const PAYMENT_STATUS_SUCCESS = 5;

    /**
     * Get payment url
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * Get payment options
     *
     * @param float $itemsAmount
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
     * @return array
     */
    public function getPaymentOptions($itemsAmount, array $transactionInfo)
    {
        return array(
            'eshopId' => ApplicationService::getSetting('payment_rbk_eshop_id'),
            'orderId' => $transactionInfo['slug'],
            'successUrl' => $this->getSuccessUrl(),
            'failUrl' => $this->getErrorUrl(),
            'serviceName' => ApplicationService::getSetting('payment_rbk_money_title'), 
            'language' => ApplicationService::getCurrentLocalization()['language'],
            'recipientAmount' => $itemsAmount,
            'recipientCurrency' => $transactionInfo['currency_code'],
            'user_email' => $transactionInfo['email']
        );
    }

    /**
     * Validate payment
     *
     * @return boolean|array
     */
    public function validatePayment()
    {
        // validate the hash
        if ($this->request->isPost() && null !== ($hash = $this->request->getPost('hash', null))) {
            $postParams = array(
                'orderId',
                'serviceName',
                'recipientAmount',
                'recipientCurrency',
                'paymentStatus',
                'userName',
                'userEmail',
                'paymentData'
            );

            $controlHash = ApplicationService::getSetting('payment_rbk_eshop_id');
            $controlHash .= '::';

            foreach ($postParams as $paramName) {
                $controlHash .= $this->request->getPost($paramName);
                $controlHash .= '::';

                if ($paramName == 'serviceName') {
                    $controlHash .= ApplicationService::getSetting('payment_rbk_account');
                    $controlHash .= '::';
                }
            }

            $controlHash .= ApplicationService::getSetting('payment_rbk_secret');

            // compare the hashes
            if ($hash == md5($controlHash) && self::PAYMENT_STATUS_SUCCESS == $this->request->getPost('paymentStatus')) {
                // get transaction info
                if (null != ($transactionInfo =
                        $this->model->getTransactionInfo($this->request->getPost('orderId'), true, 'slug'))) {

                    // check the currency code and amount
                    if ($transactionInfo['currency_code'] == $this->request->getPost('recipientCurrency')
                                && (float) $this->request->getPost('recipientAmount') >= $transactionInfo['amount']) {

                        echo 'ok';
                        return $transactionInfo;  
                    }
                }
            }
        }

        return false;
    }
}
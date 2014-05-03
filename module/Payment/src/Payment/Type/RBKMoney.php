<?php

namespace Payment\Type;

use Application\Service\Service as ApplicationService;

class RBKMoney extends AbstractType
{
    /**
     * Payment url
     * @var string
     */
    protected $paymentUrl = 'https://rbkmoney.ru/acceptpurchase.aspx';

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
}
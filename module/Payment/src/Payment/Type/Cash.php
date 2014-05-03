<?php

namespace Payment\Type;

class Cash extends AbstractType
{
    /**
     * Get payment url
     *
     * @return string
     */
    public function getPaymentUrl()
    {}

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
    {}
}
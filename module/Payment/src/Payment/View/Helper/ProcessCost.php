<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Service\Service as PaymentService;

class ProcessCost extends AbstractHelper
{
    /**
     * Process cost
     *
     * @param float|integer 
     * @return string
     */
    public function __invoke($cost)
    {
        $exchangeRates = PaymentService::getExchangeRates();
        $activeShoppingCurrency = PaymentService::getShoppingCartCurrency();

        // convert cost
        if (isset($exchangeRates[$activeShoppingCurrency])) {
            $cost = $cost * $exchangeRates[$activeShoppingCurrency]['rate'];
        }

        return $this->getView()->currencyFormat(PaymentService::roundingCost($cost), $activeShoppingCurrency);
    }
}

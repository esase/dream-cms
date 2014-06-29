<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Service\Service as PaymentService;

class ProcessCost extends AbstractHelper
{
    /**
     * Process cost
     *
     * @param float|integer $cost
     * @param boolean $rounding
     * @return string
     */
    public function __invoke($cost, $rounding = false)
    {
        $exchangeRates = PaymentService::getExchangeRates();
        $activeShoppingCurrency = PaymentService::getShoppingCartCurrency();

        // convert cost
        if (isset($exchangeRates[$activeShoppingCurrency])) {
            $cost = $cost * $exchangeRates[$activeShoppingCurrency]['rate'];
        }

        if ($rounding) {
            $cost = PaymentService::roundingCost($cost);
        }

        return $this->getView()->currencyFormat($cost, $activeShoppingCurrency);
    }
}
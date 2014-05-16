<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Service\Service as PaymentService;

class CostFormat extends AbstractHelper
{
    /**
     * Cost format
     *
     * @param float|integer|array $cost
     * @param array $options
     * @param boolean $rounding
     * @return string
     */
    public function __invoke($cost, array $options = array(), $rounding = true)
    {
        $value = null;
        $currency = null;

        // extract data from the array
        if (!is_numeric($cost)) {
            $value = !empty($cost['cost'])
                ? $cost['cost']
                : null;

            $currency = !empty($cost['currency'])
                ? $cost['currency']
                : null;
        }
        else {
            $value = $cost;
        }

        // get a currency code from the options
        if (!empty($options['currency'])) {
            $currency = $options['currency'];
        }

        if (!$value && !$currency) {
            return;
        }

        if ($rounding) {
            $value = PaymentService::roundingCost($value);
        }

        return $this->getView()->currencyFormat($value, $currency);
    }
}

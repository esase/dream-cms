<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

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
        return $this->getView()->currencyFormat($cost, "USD");
    }
}

<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class ExchangeRates extends AbstractHelper
{
   /**
    * Exchange rates
    * @var array
    */
   protected $exchangeRates;

   /**
    * Class constructor
    *
    * @param object|array $exchangeRates
    */
   public function __construct($exchangeRates = array())
   {
      $this->exchangeRates = array();
      if ($exchangeRates) {
         foreach ($exchangeRates as $currency => $rateInfo) {
            if (!$rateInfo['rate']) {
               continue;
            }

            $this->exchangeRates[$currency] = $rateInfo['rate'];
         }
      }
   }

   /**
    * Shopping cart
    *
    * @return object|array
   */
   public function __invoke()
   {
      return $this->exchangeRates;
   }
}

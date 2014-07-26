<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class PaymentShoppingCartWidget extends AbstractHelper
{
    /**
     * Payment shopping cart widget
     *
     * @return string
     */
    public function __invoke()
    {
        return $this->getView()->partial('payment/widget/shopping-cart');
    }
}
<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class PaymentInitWidget extends AbstractHelper
{
    /**
     * Payment init widget
     *
     * @return void
     */
    public function __invoke()
    {
        $this->getView()->partial('payment/widget/init');
    }
}
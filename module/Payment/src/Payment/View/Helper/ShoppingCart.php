<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Service\Service as PaymentService;

class ShoppingCart extends AbstractHelper
{
    /**
     * Shopping cart items amount
     * @var float|integer
     */
    protected $itemsAmount;

    /**
     * Shopping cart items count
     * @var integer
     */
    protected $itemsCount;

    /**
     * Class constructor
     *
     * @param object|array $items
     */
    public function __construct($items = array())
    {
        $this->itemsCount = count($items);

        // process items amount price
        foreach($items as $itemInfo) {
            $this->itemsAmount += $itemInfo['cost'] * $itemInfo['count'] - $itemInfo['discount'];
        }
    }

    /**
     * Shopping cart
     *
     * @return object - fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get items count
     *
     * @return integer
     */
    public function getItemsCount()
    {
        return $this->itemsCount;
    }

    /**
     * Get items amount
     *
     * @return float
     */
    public function getItemsAmount()
    {
        return $this->itemsAmount;
    }

    /**
     * Get items amount with discount
     *
     * @return integer
     */
    public function getItemsDiscountedAmount()
    {
        return PaymentService::getCurrentDiscount()
            ? $this->itemsAmount - ($this->itemsAmount * PaymentService::getCurrentDiscount()['discount'] / 100)
            : $this->itemsAmount;
    }

    /**
     * Get current discount
     *
     * @return integer
     */
    public function getCurrentDiscount()
    {
        return PaymentService::getCurrentDiscount() ? PaymentService::getCurrentDiscount()['discount'] : 0;
    }
}

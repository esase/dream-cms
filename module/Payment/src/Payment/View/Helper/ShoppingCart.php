<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class ShoppingCart extends AbstractHelper
{
    /**
     * Shopping cart items list
     * @var array
     */
    protected $items;

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
       $this->items = $items;
       $this->itemsCount = count($items);
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
     * @return integer
     */
    public function getItemsAmount()
    {
        $amount = 0;
        foreach($this->items as $itemInfo) {
            $amount += $itemInfo['cost'] * $itemInfo['count'] - $itemInfo['discount'];
        }

        return $amount;
    }
}

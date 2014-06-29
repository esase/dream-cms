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
     */
    public function __construct()
    {
        $this->itemsCount  = count(PaymentService::getActiveShoppingCartItems());
        $this->itemsAmount = PaymentService::getActiveShoppingCartItemsAmount();
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
        return PaymentService::getActiveShoppingCartItemsAmount(true);
    }

    /**
     * Get current discount
     *
     * @return integer
     */
    public function getCurrentDiscount()
    {
        return PaymentService::getDiscountCouponInfo()
            ? PaymentService::getDiscountCouponInfo()['discount']
            : 0;
    }
}
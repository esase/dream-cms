<?php

namespace Payment\Service;

use Application\Service\Service as ApplicationService;
use Zend\Session\Container as SessionContainer;

class Service extends ApplicationService
{
    /**
     * Primary currency
     * @var array
     */
    protected static $primaryCurrency;

    /**
     * Exchange rates
     * @var array
     */
    protected static $exchangeRates;

    /**
     * Model instance
     * @var object
     */
    protected static $model;

    /**
     * Discount coupon info
     * @var array
     */
    protected static $discountCouponInfo = null;

    /**
     * Shopping cart items
     * @var array
     */
    protected static $activeShoppingCartItems = null;

    /**
     * Shopping cart items amount
     * @var float
     */
    protected static $activeShoppingCartItemsAmount = null;

    /**
     * Get active shopping cart items
     *
     * @return array
     */
    public static function getActiveShoppingCartItems()
    {
        self::initActiveShoppingCartItems();
        return self::$activeShoppingCartItems;
    }

    /**
     * Init active shopping cart items
     *
     * @return void
     */
    protected static function initActiveShoppingCartItems()
    {
        if (self::$activeShoppingCartItems === null) {
            self::$activeShoppingCartItems = self::getModel()->getAllShoppingCartItems();
        }
    }

    /**
     * Get active shopping cart items amount 
     *
     * @param boolean $discounted
     * @return float
     */
    public static function getActiveShoppingCartItemsAmount($discounted = false)
    {
        if (null === self::$activeShoppingCartItemsAmount) {
            self::initActiveShoppingCartItems();

            // process items amount price
            self::$activeShoppingCartItemsAmount
                    = self::getModel()->getItemsAmount(self::$activeShoppingCartItems);
        }

        return $discounted && self::getDiscountCouponInfo()
            ? self::getModel()->getDiscountedItemsAmount(self::$activeShoppingCartItemsAmount, self::getDiscountCouponInfo()['discount'])
            : self::$activeShoppingCartItemsAmount;
    }

    /**
     * Get model
     */
    protected static function getModel()
    {
        if (!self::$model) {
            self::$model = self::$serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\Base');
        }

        return self::$model;
    }

    /**
     * Set a discount coupon ID
     *
     * @param integer $couponId
     * @return void
     */
    public static function setDiscountCouponId($couponId)
    {
        $paymentSession = new SessionContainer('payment');
        $paymentSession->discountCouponId = $couponId;
    }

    /**
     * Get discount coupon info
     *
     * @return array
     */
    public static function getDiscountCouponInfo()
    {
        if (self::$discountCouponInfo === null) {
            // get a session
            $paymentSession = new SessionContainer('payment');

            if (!empty($paymentSession->discountCouponId)) {
                // get a discount coupon info
                if (null != ($discountInfo =
                        self::getModel()->getActiveCouponInfo($paymentSession->discountCouponId, 'id'))) {

                    self::$discountCouponInfo = $discountInfo;
                    return $discountInfo;
                }

                // remove the discount from the session
                $paymentSession->discountCouponId = null;
            }

            self::$discountCouponInfo = array();
        }

        return self::$discountCouponInfo;
    }

    /**
     * Init exchange rates
     *
     * @return void
     */
    protected static function initExchangeRates()
    {
        foreach (self::getModel()->getExchangeRates(false) as $currency => $currencyInfo) {
            // get primary currency
            if ($currencyInfo['primary_currency']) {
                self::$primaryCurrency = array(
                    'id'   => $currencyInfo['id'],
                    'name' => $currencyInfo['name'],
                    'code' => $currencyInfo['code']
                );

                continue;
            }

            if (!$currencyInfo['rate']) {
                continue;
            }

            self::$exchangeRates[$currency] = array(
                'id'   => $currencyInfo['id'],
                'rate' => $currencyInfo['rate'],
                'name' => $currencyInfo['name'],
                'code' => $currencyInfo['code']
            );
        }
    }

    /**
     * Get exchange rates
     *
     * @return array
     */
    public static function getExchangeRates()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        return self::$exchangeRates;
    }

    /**
     * Get primary currency
     *
     * @return array
     */
    public static function getPrimaryCurrency()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        return self::$primaryCurrency;
    }

    /**
     * Get shopping cart currency
     *
     * @return string
     */
    public static function getShoppingCartCurrency()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        $shoppingCartCurrency = self::getModel()->getShoppingCartCurrency();

        if (!$shoppingCartCurrency) {
            return self::$primaryCurrency['code'];
        }

        return self::$exchangeRates && array_key_exists($shoppingCartCurrency, self::$exchangeRates)
            ? self::$exchangeRates[$shoppingCartCurrency]['code']
            : self::$primaryCurrency['code'];         
    }

    /**
     * Rounding a cost
     *
     * @param float|integer $cost
     * @return integer|float
     */
    public static function roundingCost($cost)
    {
        switch (self::getSetting('payment_type_rounding')) {
            case 'type_round' :
                return round($cost);

            case 'type_ceil' :
                return ceil($cost);

            case 'type_floor' :
                return floor($cost);

            default :
                return $cost;
        }
    }
}
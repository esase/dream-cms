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
     * Set current discount
     *
     * @param array $couponInfo
     *      integer id
     *      string slug
     *      float discount
     *      integer activated
     *      integer date_start
     *      integer date_end
     * @return void
     */
    public static function setCurrentDiscount($couponInfo)
    {
        $paymentSession = new SessionContainer('payment');
        $paymentSession->currentDiscount = $couponInfo;
    }

    /**
     * Get current discount
     *
     * @return array
     */
    public static function getCurrentDiscount()
    {
        $paymentSession = new SessionContainer('payment');
        return isset($paymentSession->currentDiscount)
            ? $paymentSession->currentDiscount
            : array();
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
                    'name' => $currencyInfo['name'],
                    'code' => $currencyInfo['code']
                );

                continue;
            }

            if (!$currencyInfo['rate']) {
                continue;
            }

            self::$exchangeRates[$currency] = array(
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
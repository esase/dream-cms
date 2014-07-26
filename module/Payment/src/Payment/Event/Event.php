<?php
namespace Payment\Event;

use Application\Event\AbstractEvent as AbstractEvent;
use User\Service\Service as UserService;
use Application\Utility\EmailNotification;

class Event extends AbstractEvent
{
    /**
     * Add payment currency event
     */
    const ADD_PAYMENT_CURRENCY = 'add_payment_currency';

    /**
     * Edit payment currency event
     */
    const EDIT_PAYMENT_CURRENCY = 'edit_payment_currency';

    /**
     * Delete payment currency event
     */
    const DELETE_PAYMENT_CURRENCY = 'delete_payment_currency';

    /**
     * Edit exchange rates event
     */
    const EDIT_EXCHANGE_RATES = 'edit_exchange_rates';

    /**
     * Delete discount coupon event
     */
    const DELETE_DISCOUNT_COUPON = 'delete_discount_coupon';

    /**
     * Add discount coupon event
     */
    const ADD_DISCOUNT_COUPON = 'add_discount_coupon';

    /**
     * Edit discount coupon event
     */
    const EDIT_DISCOUNT_COUPON = 'edit_discount_coupon';

    /**
     * Activate discount coupon event
     */
    const ACTIVATE_DISCOUNT_COUPON = 'activate_discount_coupon';

    /**
     * Deactivate discount coupon event
     */
    const DEACTIVATE_DISCOUNT_COUPON = 'deactivate_discount_coupon';

    /**
     * Add item to shopping cart event
     */
    const ADD_ITEM_TO_SHOPPING_CART = 'add_item_to_shopping_cart';

    /**
     * Delete item from shopping cart event
     */
    const DELETE_ITEM_FROM_SHOPPING_CART = 'delete_item_from_shopping_cart';

    /**
     * Edit item into shopping cart event
     */
    const EDIT_ITEM_INTO_SHOPPING_CART = 'edit_item_into_shopping_cart';

    /**
     * Add payment transaction event
     */
    const ADD_PAYMENT_TRANSACTION = 'add_payment_transaction';

    /**
     * Delete payment transaction event
     */
    const DELETE_PAYMENT_TRANSACTION = 'delete_payment_transaction';

    /**
     * Activate payment transaction event
     */
    const ACTIVATE_PAYMENT_TRANSACTION = 'activate_payment_transaction';

    /**
     * Mark as deleted payment transactions and shopping cart items event
     */
    const MARK_DELETED_ITEMS = 'mark_deleted_payment_items';

    /**
     * Edit items event
     */
    const EDIT_ITEMS = 'edit_payment_items';

    /**
     * Hide payment transaction event
     */
    const HIDE_PAYMENT_TRANSACTION = 'hide_payment_transaction';

    /**
     * Fire edit items event
     *
     * @param integer $objectId
     * @param integer $moduleId
     * @return void
     */
    public static function fireEditItemsEvent($objectId, $moduleId)
    {
        // event's description
        $eventDesc = 'Event - Shopping cart and transactions items were edited by the system';
        self::fireEvent(self::EDIT_ITEMS, $objectId, self::getUserId(true), $eventDesc, array(
            $objectId, 
            $moduleId
        ));
    }

    /**
     * Fire mark deleted items event
     *
     * @param integer $objectId
     * @param integer $moduleId
     * @return void
     */
    public static function fireMarkDeletedItemsEvent($objectId, $moduleId)
    {
        // event's description
        $eventDesc = 'Event - Shopping cart and transactions items were edited by the system';
        self::fireEvent(self::MARK_DELETED_ITEMS, $objectId, self::getUserId(true), $eventDesc, array(
            $objectId, 
            $moduleId
        ));
    }

    /**
     * Fire activate payment transaction event
     *
     * @param integer $transactionId
     * @param boolean $isSystemEvent
     * @param array $transactionInfo
     *      string first_name
     *      string last_name
     *      string email
     *      string id
     * @return void
     */
    public static function fireActivatePaymentTransactionEvent($transactionId, $isSystemEvent = false, $transactionInfo = array())
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - Payment transaction activated by the system'
            : (UserService::isGuest() ? 'Event - Payment transaction activated by guest'
                    : 'Event - Payment transaction activated by user');

        $eventDescParams = $isSystemEvent
            ? array($transactionId)
            : (UserService::isGuest() ? array($transactionId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $transactionId));

        self::fireEvent(self::ACTIVATE_PAYMENT_TRANSACTION, 
                $transactionId, self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);

        // send an email notification about the paid transaction
        if ($transactionInfo && (int) UserService::getSetting('payment_transaction_paid')) {
            EmailNotification::sendNotification(UserService::getSetting('application_site_email'),
                UserService::getSetting('payment_transaction_paid_title', UserService::getDefaultLocalization()['language']),
                UserService::getSetting('payment_transaction_paid_message', UserService::getDefaultLocalization()['language']), array(
                    'find' => array(
                        'FirstName',
                        'LastName',
                        'Email',
                        'Id'
                    ),
                    'replace' => array(
                        $transactionInfo['first_name'],
                        $transactionInfo['last_name'],
                        $transactionInfo['email'],
                        $transactionInfo['id']
                    )
                ));
        }
    }

    /**
     * Fire hide payment transaction event
     *
     * @param integer $transactionId
     * @return void
     */
    public static function fireHidePaymentTransactionEvent($transactionId)
    {
        // event's description
        $eventDesc = 'Event - Payment transaction hide by user';
        $eventDescParams = array(UserService::getCurrentUserIdentity()->nick_name, $transactionId);

        self::fireEvent(self::HIDE_PAYMENT_TRANSACTION, 
                $transactionId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete payment transaction event
     *
     * @param integer $transactionId
     * @param string $type (available types are: system, user)
     * @return void
     */
    public static function fireDeletePaymentTransactionEvent($transactionId, $type = null)
    {
        switch($type) {
            case 'system' :
                $eventDesc = 'Event - Payment transaction deleted by the system';
                $eventDescParams = array($transactionId);
                break;

            case 'user' :
                $eventDesc = 'Event - Payment transaction deleted by user';
                $eventDescParams = array(UserService::getCurrentUserIdentity()->nick_name, $transactionId);
                break;

            default :
                // event's description
                $eventDesc = UserService::isGuest()
                    ? 'Event - Payment transaction deleted by guest'
                    : 'Event - Payment transaction deleted by user';

                $eventDescParams = UserService::isGuest()
                    ? array($transactionId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $transactionId);
        }

        self::fireEvent(self::DELETE_PAYMENT_TRANSACTION, 
                $transactionId, self::getUserId(($type == 'system')), $eventDesc, $eventDescParams);
    }

    /**
     * Fire add payment transaction event
     *
     * @param string $transactionId
     * @param array $userInfo
     *      string first_name
     *      string last_name
     *      string email
     * @return void
     */
    public static function fireAddPaymentTransactionEvent($transactionId, $userInfo)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Payment transaction added by guest'
            : 'Event - Payment transaction added by user';

        $eventDescParams = UserService::isGuest()
            ? array($transactionId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $transactionId);

        self::fireEvent(self::ADD_PAYMENT_TRANSACTION, 
                $transactionId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

        // send an email notification about register the new transaction
        if ((int) UserService::getSetting('payment_transaction_add')) {
            EmailNotification::sendNotification(UserService::getSetting('application_site_email'),
                UserService::getSetting('payment_transaction_add_title', UserService::getDefaultLocalization()['language']),
                UserService::getSetting('payment_transaction_add_message', UserService::getDefaultLocalization()['language']), array(
                    'find' => array(
                        'FirstName',
                        'LastName',
                        'Email',
                        'Id'
                    ),
                    'replace' => array(
                        $userInfo['first_name'],
                        $userInfo['last_name'],
                        $userInfo['email'],
                        $transactionId
                    )
                ));
        }
    }

    /**
     * Fire edit item into shopping cart event
     *
     * @param integer $itemId
     * @return void
     */
    public static function fireEditItemIntoShoppingCartEvent($itemId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Item edited into the shopping cart by guest'
            : 'Event - Item edited into the shopping cart by user';

        $eventDescParams = UserService::isGuest()
            ? array($itemId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $itemId);

        self::fireEvent(self::EDIT_ITEM_INTO_SHOPPING_CART, 
                $itemId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete item from shopping cart event
     *
     * @param integer $itemId
     * @param boolean $isSystemEvent
     * @return void
     */
    public static function fireDeleteItemFromShoppingCartEvent($itemId, $isSystemEvent = false)
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - Item deleted from shopping cart by the system'
            : (UserService::isGuest() ? 'Event - Item deleted from shopping cart by guest'
                    : 'Event - Item deleted from shopping cart by user');

        $eventDescParams = $isSystemEvent
            ? array($itemId)
            : (UserService::isGuest() ? array($itemId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $itemId));

        self::fireEvent(self::DELETE_ITEM_FROM_SHOPPING_CART, 
                $itemId, self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);
    }

    /**
     * Fire add item to shopping cart event
     *
     * @param integer $itemId
     * @return void
     */
    public static function fireAddItemToShoppingCartEvent($itemId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Item added to shopping cart by guest'
            : 'Event - Item added to shopping cart by user';

        $eventDescParams = UserService::isGuest()
            ? array($itemId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $itemId);

        self::fireEvent(self::ADD_ITEM_TO_SHOPPING_CART, 
                $itemId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire deactivate discount coupon event
     *
     * @param string $couponCode
     * @return void
     */
    public static function fireDeactivateDiscountCouponEvent($couponCode)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Discount coupon deactivated by guest'
            : 'Event - Discount coupon deactivated by user';

        $eventDescParams = UserService::isGuest()
            ? array($couponCode)
            : array(UserService::getCurrentUserIdentity()->nick_name, $couponCode);

        self::fireEvent(self::DEACTIVATE_DISCOUNT_COUPON, 
                $couponCode, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire activate discount coupon event
     *
     * @param string $couponCode
     * @return void
     */
    public static function fireActivateDiscountCouponEvent($couponCode)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Discount coupon activated by guest'
            : 'Event - Discount coupon activated by user';

        $eventDescParams = UserService::isGuest()
            ? array($couponCode)
            : array(UserService::getCurrentUserIdentity()->nick_name, $couponCode);

        self::fireEvent(self::ACTIVATE_DISCOUNT_COUPON, 
                $couponCode, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit discount coupon event
     *
     * @param integer $couponId
     * @return void
     */
    public static function fireEditDiscountCouponEvent($couponId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Discount coupon edited by guest'
            : 'Event - Discount coupon edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($couponId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $couponId);

        self::fireEvent(self::EDIT_DISCOUNT_COUPON, 
                $couponId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire add discount coupon event
     *
     * @param integer $couponId
     * @return void
     */
    public static function fireAddDiscountCouponEvent($couponId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Discount coupon added by guest'
            : 'Event - Discount coupon added by user';

        $eventDescParams = UserService::isGuest()
            ? array($couponId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $couponId);

        self::fireEvent(self::ADD_DISCOUNT_COUPON, 
                $couponId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete discount coupon  event
     *
     * @param integer $couponId
     * @return void
     */
    public static function fireDeleteDiscountCouponEvent($couponId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Discount coupon deleted by guest'
            : 'Event - Discount coupon deleted by user';

        $eventDescParams = UserService::isGuest()
            ? array($couponId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $couponId);

        self::fireEvent(self::DELETE_DISCOUNT_COUPON, 
                $couponId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit exchange rates event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireEditExchangeRatesEvent($currencyId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Payment exchange rates edited by guest'
            : 'Event - Payment exchange rates edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($currencyId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $currencyId);

        self::fireEvent(self::EDIT_EXCHANGE_RATES, 
                $currencyId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireDeletePaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Payment currency deleted by guest'
            : 'Event - Payment currency deleted by user';

        $eventDescParams = UserService::isGuest()
            ? array($currencyId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $currencyId);

        self::fireEvent(self::DELETE_PAYMENT_CURRENCY, 
                $currencyId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireEditPaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Payment currency edited by guest'
            : 'Event - Payment currency edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($currencyId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $currencyId);

        self::fireEvent(self::EDIT_PAYMENT_CURRENCY, 
                $currencyId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire add payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireAddPaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Payment currency added by guest'
            : 'Event - Payment currency added by user';

        $eventDescParams = UserService::isGuest()
            ? array($currencyId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $currencyId);

        self::fireEvent(self::ADD_PAYMENT_CURRENCY, 
                $currencyId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }
}
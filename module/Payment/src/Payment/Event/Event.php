<?php

namespace Payment\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
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
}
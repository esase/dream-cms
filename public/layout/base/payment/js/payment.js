Payment = function()
{
    /**
     * Server url
     * @var string
     */
    var serverUrl;

    /**
     * Popup shopping's cart window id
     * @var string
     */
    var popupShoppingCartId = '#popup-shopping-cart-window';

    /**
     * Shopping cart wrapper
     * @var string
     */
    var shoppingCartWrapper = 'shopping-cart-wrapper';

    /**
     * Sopping cart clear fix
     * @var string
     */
    var shoppingCartClearFix = 'shopping-cart-clearfix';

    /**
     * Current object
     * @var object
     */
    var self = this;

    /**
     * Hide popup shopping
     * @var boolean
     */
    var hidePopupShopping = false;

    /**
     * Show popup's shopping cart
     *
     * @param string action
     * @param object|string data
     * @param string method
     * @return void
     */
    var _showPopupShoppingCart = function(action, data, method)
    {
        $.ajax({
            'type'      : typeof method != 'undefined' ? method : "post",
            'url'       : serverUrl + '/' + action,
            'data'      : data,
            'success'   : function(data) {
                $(document.body).append(data);

                if (!hidePopupShopping) {
                    $(popupShoppingCartId).on('hidden.bs.modal', function (e) {
                       $(this).remove();
                    }).modal('show');
                }
                else {
                    hidePopupShopping = false;
                }
            }
        });
    }

    /**
     * Update shopping cart
     *
     * @param string data
     * @return void
     */
    var _updateShoppingCart = function(data)
    {
        $('#' + shoppingCartClearFix).remove();
        $('#' + shoppingCartWrapper).replaceWith(data);
    }

    /**
     * Refresh page
     *
     * @return void
     */
    this.refreshPage = function()
    {
        location.href = document.location;
    }

    /**
     * Add to shopping cart
     *
     * @param integer objectId
     * @param string module
     * @param integer count
     * @param object extraOptions
     * @return void
     */
    this.addToShoppingCart = function(objectId, module, count, extraOptions)
    {
        var baseOptions = {
            'object_id': objectId,
            'module': module,
            'count' : (typeof count != 'undefined' ? count : 0)
        }

        // merge the extra and base options
        if (typeof extraOptions != 'undefined') {
            baseOptions = $.extend({}, baseOptions, extraOptions);
        }

        _showPopupShoppingCart('ajax-add-to-shopping-cart', baseOptions);
    }

    /**
     * Send a shopping cart's form
     *
     * @return void
     */
    this.sendShoppingCartForm = function()
    {
        var $popup = $(popupShoppingCartId);

        // remove previously loaded popup
        $popup.on('hidden.bs.modal', function (e) {
            _showPopupShoppingCart('ajax-add-to-shopping-cart', $popup.find('form:first').serialize());
        }).modal('hide');
    }

    /**
     * Update shopping cart
     *
     * @return void
     */
    this.updateShoppingCart = function()
    {
        showLoadingBox(shoppingCartWrapper);

        // update the shopping cart
        $.get(serverUrl + '/ajax-update-shopping-cart?_r=' + Math.random(), function(data) {
            _updateShoppingCart(data);
        });
    }

    /**
     * Change currency
     *
     * @return void
     */
    this.changeCurrency = function(currency)
    {
        showLoadingBox(shoppingCartWrapper);

        // update the shopping cart
        $.post(serverUrl + '/ajax-change-currency', {'currency' : currency}, function(data) {
            // refresh current page
            self.refreshPage();
        });
    }

    /**
     * Get a discount coupon form
     *
     * @return void
     */
    this.getDiscountCouponForm = function()
    {
        _showPopupShoppingCart('ajax-activate-discount-coupon', {}, 'get');
    }

    /**
     * Send a discount coupon form
     *
     * @return void
     */
    this.sendDiscountCouponForm = function()
    {
        var $popup = $(popupShoppingCartId);

        // remove previously loaded popup
        $popup.on('hidden.bs.modal', function (e) {
            _showPopupShoppingCart('ajax-activate-discount-coupon', $popup.find('form:first').serialize());
        }).modal('hide');
    }

    /**
     * Get an edit item form
     *
     * @param integer itemId
     * @return void
     */
    this.getEditItemForm = function(itemId)
    {
        _showPopupShoppingCart('ajax-edit-shopping-cart-item/' + parseInt(itemId), {}, 'get');
    }

    /**
     * Send a an item form
     *
     * @param integer itemId
     * @return void
     */
    this.sendEditItemForm = function(itemId)
    {
        var $popup = $(popupShoppingCartId);

        // remove previously loaded popup
        $popup.on('hidden.bs.modal', function (e) {
            _showPopupShoppingCart('ajax-edit-shopping-cart-item/' + parseInt(itemId), $popup.find('form:first').serialize());
        }).modal('hide');
    }

    /**
     * Deactivate a discount coupon
     *
     * @return void
     */
    this.deactivateDiscountCoupon = function()
    {
        $.post(serverUrl + '/ajax-deactivate-discount-coupon', function(data) {
            // refresh current page
            self.refreshPage();
        });
    }

    /**
     * Hide popup shopping
     *
     * @return void
     */
    this.hidePopupShopping = function()
    {
        hidePopupShopping = true;
    }

    /**
     * Clean shopping cart
     *
     * @param boolean isShoppingCartPage
     * @return void
     */
    this.cleanShoppingCart = function(isShoppingCartPage)
    {
        showLoadingBox(shoppingCartWrapper);

        // update the shopping cart
        $.post(serverUrl + '/ajax-clean-shopping-cart', function(data) {
            typeof isShoppingCartPage != 'undefined' && true == isShoppingCartPage
                ? self.refreshPage()
                : _updateShoppingCart(data);
        });
    }

    /**
     * Set server url
     *
     * @param sting url
     * @return object - fluent interface
     */
    this.setServerUrl = function(url)
    {
        serverUrl = url;
        return this;
    }
}
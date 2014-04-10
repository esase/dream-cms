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
     * Current object
     * @var object
     */
    var self = this;

    /**
     * Show popup's shopping cart
     *
     * @param object|string data
     * @return void
     */
    var showPopupShoppingCart = function(data)
    {
        // send form data
        $.post(serverUrl + '/add-to-shopping-cart', data, function(data) {
            $(document.body).append(data);
            $(popupShoppingCartId).on('hidden.bs.modal', function (e) {
               $(this).remove();
            }).modal('show');
        });
    }

    /**
     * Add to shopping cart
     *
     * @param integer objectId
     * @param string module
     * @param integer count
     * @return void
     */
    this.addToShoppingCart = function(objectId, module, count)
    {
        showPopupShoppingCart({
            'object_id': objectId,
            'module': module,
            'count' : (typeof count != 'undefined' ? count : 0)
        });
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
            showPopupShoppingCart($popup.find('form:first').serialize());
        }).modal('hide');
    }

    /**
     * Update shopping cart
     *
     * @return void
     */
    this.updateShoppingCart = function()
    {
        alert('update shopping cart');
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

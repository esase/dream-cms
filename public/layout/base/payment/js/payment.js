Payment = function()
{
    /**
     * Server url
     * @var string
     */
    var serverUrl;

    /**
     * Popup modal id
     * @var string
     */
    var popupModalId = '#popup-payment';

    /**
     * Current object
     * @var object
     */
    var self = this;

    /**
     * Add to basket
     *
     * @param integer objectId
     * @param string module
     * @return void
     */
    this.addToBasket = function(objectId, module)
    {
        // remove previously loaded popup
        $(popupModalId).remove();

        $.post(this.serverUrl + '/' + 'add-to-basket', {'objectId': objectId, 'module': module}, function(data) {
            $(document.body).append(data);
            $(popupModalId).modal('show');
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
        this.serverUrl = url;
        return this;
    }
}

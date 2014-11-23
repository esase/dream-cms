/**
 * Widget
 *
 * @param object options
 *      string widgetsRefreshUrl
 *      string widgetsWrapper
 */
function Widget(options)
{
    /**
     * Widget options
     * @var object
     */
    var widgetOptions = options;

    /**
     * Refresh page
     *
     * @return void
     */
    var refreshPage = function()
    {
        ajaxQuery(widgetOptions.widgetsWrapper, widgetOptions.widgetsRefreshUrl);
    }

    /**
     * Get page
     *
     * @param string url
     * @param object
     * @param sting method
     * @return void
     */
    this.getPage = function(url, params, method)
    {
        method = typeof method == 'undefined' ? 'post' : method;

        ajaxQuery(widgetOptions.widgetsWrapper, url, function(data) {
            // refresh the page
            refreshPage();
        }, method, params, false);
    }
}
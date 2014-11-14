/**
 * Widget
 *
 * @param object options
 * 		string widgetsWrapper
 * 		sting newWidgetsWrapper
 * 		string newWidgetLink
 * 		string newWidgetUrl
 * 		integer pageId
 */
function Widget(options)
{
	/**
	 * Widget options
	 * @var object
	 */
	var widgetOptions = options;

	/**
     * New widgets wrapper
     * @var object
     */
    var $newWidgetsWrapper = $(options.newWidgetsWrapper);

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
	 * Init widget
	 *
	 * @return void
	 */
	var initWidget = function()
	{
		// listen to new widgets links
		$newWidgetsWrapper.find(widgetOptions.newWidgetLink).click(function(e){
			e.preventDefault();

			params = {
				'widget' : $(this).attr('widget'),
				'page' : widgetOptions.pageId
			};

			ajaxQuery(widgetOptions.widgetsWrapper, widgetOptions.newWidgetUrl, function(data) {
				// refresh the page
				refreshPage();
			}, 'post', params, false);
		});
	}

	// init 
    initWidget();
}
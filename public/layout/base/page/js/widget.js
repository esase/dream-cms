/**
 * Widget
 *
 * @param object options
 * 		boolean initSortable
 * 		string sortableWidgtes
 * 		string widgetsWrapper
 * 		boolean initNewWidgets
 * 		sting newWidgetsWrapper
 * 		string newWidgetLink
 * 		string newWidgetUrl
 * 		string changeWidgetPositionUrl
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
		// init sortable widgets
		if (widgetOptions.initSortable) {
			$(options.sortableWidgtesWrapper).sortable({
				connectWith: options.sortableWidgtesWrapper,
				placeholder: options.sortableWidgtesPlaceholder,
				stop: function (e, ui) {
					params = {
						'widget_order' : $(ui.item).index(),
						'widget_connection'  : $(ui.item).attr('widget-connection'),
						'widget_position' : $(ui.item).parent().attr('position')
					};

					// save a selected widget's position
					ajaxQuery(widgetOptions.widgetsWrapper, widgetOptions.changeWidgetPositionUrl, function(data) {
						// refresh the page
						refreshPage();
					}, 'post', params, false);
				}
			}).disableSelection();
		}

		// listen to new widgets links
		if (widgetOptions.initNewWidgets) {
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
	}

	// init 
    initWidget();
}
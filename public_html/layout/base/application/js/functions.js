
/**
 * Uniform height
 *
 * @param string elements
 * @param boolean bindWindowResize
 * @return void
 */
function uniformHeight(elements, bindWindowResize)
{
    var maxHeight = 0, max = Math.max;

    $(elements).each(function() {
        $(this).css('height', 'auto')
        maxHeight = max(maxHeight, $(this).height());
    }).height(maxHeight);

    // change elements height if window's size will change
    if (typeof bindWindowResize != 'undefined' && bindWindowResize == true) { 
        $(window).resize(function(){
            uniformHeight(elements);
        });
    }
}

/**
 * Data list query
 *
 * @param string container
 * @param string url
 * @param integer widgetConnectionId
 * @param string widgetPosition
 * @param string extraUrlParams
 * @return void
 */
function dataListQuery(container, url, widgetConnectionId, widgetPosition, extraUrlParams)
{
    var paramDevider = url.match(/\?/);
    url += paramDevider 
        ? '&widget_connection=' + widgetConnectionId + '&widget_position=' + widgetPosition
        : '?widget_connection=' + widgetConnectionId + '&widget_position=' + widgetPosition;

    if (typeof extraUrlParams != 'undefined') {
        url += '&' + extraUrlParams;
    }

    ajaxQuery(container, url);
}

/**
 * Ajax query
 *
 * @param string container
 * @param string url
 * @param object successCallback
 * @param string method
 * @param object params
 * @param boolean replaceData
 * @return void
 */
function ajaxQuery(container, url, successCallback, method, params, replaceData)
{
    // show a loading box
    showLoadingBox(container);

    $.ajax({
        type: typeof method != 'undefined' && method == 'post' ? method : 'get',
        url: url,
        cache: false,
        data: params,
        success: function(data){
            // replace text into a container
            if (typeof replaceData  == 'undefined' || replaceData == true) {
                $('#' + container).html(data);
            }
            else {
                // remove the loading box
                $('#' + container).find('.loading-ajax-wrapper').remove();
            }

            // call a callback
            if (typeof successCallback != 'undefined' && successCallback) {
                successCallback.call(this, data);
            }
        }
    });
}

/**
 * Show confirm popup
 *
 * @param string confirmTitle
 * @param string cancelTitle
 * @param object link
 * @param function callback
 */
function showConfirmPopup(confirmTitle, cancelTitle, link, callback)
{
    var $link = $(link);

    // confirm buttons
    var $confirmButtons = $('<a action="confirm">' + confirmTitle + '</a>&nbsp;<a action="cancel">' + cancelTitle + '</a>')
        .attr('class', 'btn btn-default')
        .click(function(event){
            event.preventDefault();

            switch($(this).attr('action')) {
                case 'confirm' :
                    callback.call(this, link);
                    break;
            }

            $link.popover("destroy");
        });

    // show confirm message
    $link.popover({
        trigger: "manual",
        placement: "bottom",
        html: true,
        title : $link.attr('confirm'),
        content: $confirmButtons
    });

    $link.popover("show");
}

/**
 * Show loading box
 * 
 * @param jquery object|string container
 * @return void
 */
function showLoadingBox(container)
{
    if ($.type(container) === "string") {
        container = $('#' + container);
    }

    var loadingDiv = jQuery(
        '<div class="loading-ajax-wrapper"><div class="loading-ajax"></div></div>'
    ).appendTo(container);

    var iLeftOff = container.width()  / 2 - (loadingDiv.find('div.loading-ajax:first').width()  / 2);
    var iTopOff  = container.height() / 2 - (loadingDiv.find('div.loading-ajax:first').height() / 2) + 10;

    if (iTopOff<0) {
        iTopOff = 0;
    }

    loadingDiv.css({
        position: 'absolute',
        left: iLeftOff,
        top:  iTopOff,
        zIndex:100
    });
}

/**
 * Show popup window
 *
 * @param string url
 * @param string popupId
 * @return void
 */
function showPopup(url, popupId)
{
    // remove previously opened popup
    $('#' + popupId).remove();

    $.ajax({
    	'type'      : "get",
    	'url'       : url,
		'cache' 	: false,
    	'success'   : function(data) {
    	    $(document.body).append(data);
    	    $('#' + popupId).modal('show');
    	}
    });
}

/**
 * Show loaded popup window
 *
 * @param string popupId
 * @param string data
 * @return void
 */
function showLoadedPopup(popupId, data)
{
    // remove previously opened popup
    $('#' + popupId).remove();

	$(document.body).append(data);
	$('#' + popupId).modal('show');
}
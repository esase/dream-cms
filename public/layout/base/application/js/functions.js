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

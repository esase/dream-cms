/**
 * Show loading box
 * 
 * @param jquery object container
 * @return void
 */
function showLoadingBox(container)
{
    var loadingDiv = jQuery(
        '<div class="loading-ajax">' +
            '<img style="margin-top: 16px;" alt="Loading..." src="' + baseUrl + 'images/loading.gif" />' +
        '</div>'
    ).appendTo(container);

    var iLeftOff = container.width()  / 2 - (loadingDiv.find('img:first').width()  / 2);
    var iTopOff  = container.height() / 2 - (loadingDiv.find('img:first').height() / 2) + 10;
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
 * Submit form
 *
 * @param string formId
 * @param string url
 * @param string formWrapper
 * @return void
 */
function submitForm(formId, url, formWrapper)
{
    var $form  = jQuery('#' + formId);

    // disable all submit inputs
    $form.find('input[type="submit"]').attr('disabled','disabled');

    var processedUrl = typeof url == 'undefined' || !$.trim(url) 
        ? document.location
        : url;

    showLoadingBox($form);
    jQuery.post(processedUrl, $form.serialize(), function(data){
            typeof formWrapper == 'undefined' 
                ? $form.replaceWith(data)
                : $('#' + formWrapper).replaceWith(data);
        }
    )
}
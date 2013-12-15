/**
 * Data grid
 *
 * @param object options
 */
function DataGrid(options)
{
    /**
     * Form id
     * @var string
     */
    this.formId = options.formId;

    /**
     * Links class
     * @var string
     */
    this.linksClass = options.linksClass;

    /**
     * Items uid
     * @var string
     */
    this.items = options.items;

    /**
     * Selector id
     * @var string
     */
    this.selectorId = options.selectorId;

    /**
     * Selector error title
     * @var string
     */
    this.selectorErrorTitle = options.selectorErrorTitle;

    /**
     * Selector error content
     * @var string
     */
    this.selectorErrorContent = options.selectorErrorContent;

    /**
     * Selector error time
     * @var integer
     */
    this.selectorErrorTime = options.selectorErrorTime;

    /**
     * Confirm button title
     * @var string
     */
    this.confirmButtonTitle = options.confirmButtonTitle;

    /**
     * Cancel button title
     * @var string
     */
    this.cancelButtonTitle = options.cancelButtonTitle;

    // init data grid
    this._initDataGrid();
}

/**
 * Init data grid
 *
 * @return void
 */
DataGrid.prototype._initDataGrid = function()
{
    var self = this;

    $(this.linksClass).click(function(event){
        event.preventDefault();

        var $form = $(self.formId);

        // check the selected items
        var $checkboxItem = $form.find('input[name="' + self.items + '"]:enabled:checked');

        if (!$checkboxItem.val()) {
            var $popover = $(self.selectorId);
            $popover.popover({
                trigger: "manual",
                placement: "right",
                title : self.selectorErrorTitle,
                content: self.selectorErrorContent
            });

            $popover.popover("show");

            setTimeout(function () {
                $popover.popover("hide")
            }, self.selectorErrorTime);
        }
        else {
            // check confirm attr
            if ($(this).attr('confirm')) {
                event.stopPropagation();

                var $link = $(this);
                $link.popover("destroy");

                var $confirmButtons = $('<a action="confirm">' +
                            self.confirmButtonTitle + '</a>&nbsp;<a action="cancel">' + self.cancelButtonTitle + '</a>')
                    .attr('class', 'btn')
                    .click(function(event){
                        event.preventDefault();

                        switch($(this).attr('action')) {
                            case 'confirm' :
                                // send a form
                                $form.attr('action', $link.attr('href')).submit();
                                break;
                        }

                        $link.popover("destroy");
                    });

                // show confirm message
                $link.popover({
                    trigger: "manual",
                    placement: "top",
                    html: true,
                    title : $(this).attr('confirm'),
                    content: $confirmButtons
                });

                $link.popover("show");
            }
            else {
                $form.attr('action', $(this).attr('href')).submit();
            }
        }
    });
}
/**
 * Data grid
 *
 * @param object options
 */
function DataGrid(options)
{
    /**
     * Form
     * @var object
     */
    this.$form = $(options.formId);

    /**
     * Multiple actions class
     * @var string
     */
    this.multipleActionsClass = options.multipleActionsClass;

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

    /**
     * Language direction
     * @var string
     */
    this.langDirection = options.langDirection;

    /**
     * Error time handler
     * @var object
     */
    this.errorTimeHandler;

    // init data grid
    this._initDataGrid();
}

/**
 * Show error
 *
 * @return void
 */
DataGrid.prototype._showError = function()
{
    var $popover = $(this.selectorId);
    $popover.popover("destroy");

    if (this.errorTimeHandler) {
        clearTimeout(this.errorTimeHandler);
    }

    $popover.popover({
        trigger: "manual",
        placement: (this.langDirection == 'ltr' ?  "right" : "left"),
        title : this.selectorErrorTitle,
        content: this.selectorErrorContent
    });

    $popover.popover("show");

    this.errorTimeHandler = setTimeout(function () {
        $popover.popover("hide")
    }, this.selectorErrorTime);
}

/**
 * Init data grid
 *
 * @return void
 */
DataGrid.prototype._initDataGrid = function()
{
    var self = this;

    // init multiple actions clicks
    $(this.multipleActionsClass).click(function(event){
        event.preventDefault();

        if (!self._checkCheckedItems()) {
            self._showError();
        }
        else {
            // check confirm attr
            if ($(this).attr('confirm')) {
                event.stopPropagation();

                var $link = $(this);
                $link.popover("destroy");

                // confirm buttons
                var $confirmButtons = $('<a action="confirm">' +
                            self.confirmButtonTitle + '</a>&nbsp;<a action="cancel">' + self.cancelButtonTitle + '</a>')
                    .attr('class', 'btn btn-default')
                    .click(function(event){
                        event.preventDefault();

                        switch($(this).attr('action')) {
                            case 'confirm' :
                                if (!self._checkCheckedItems()) {
                                    self._showError();
                                }
                                else {
                                    self.$form.attr('action', $link.attr('href')).submit();
                                }
                                break;
                        }

                        $link.popover("destroy");
                    });

                // show confirm message
                $link.popover({
                    trigger: "manual",
                    placement: "bottom",
                    html: true,
                    title : $(this).attr('confirm'),
                    content: $confirmButtons
                });

                $link.popover("show");
            }
            else {
                self.$form.attr('action', $(this).attr('href')).submit();
            }
        }
    });
}

/**
 * Check checked items
 *
 * @return boolean
 */
DataGrid.prototype._checkCheckedItems= function()
{
    // check selected items
    var $checkboxItem = this.$form
        .find('input[name="' + this.items + '"]:enabled:checked');

    return $checkboxItem.val() ? true : false;
}
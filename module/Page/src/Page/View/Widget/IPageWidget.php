<?php
namespace Page\View\Widget;

interface IPageWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent();

    /**
     * Set current page id
     *
     * @param integer $pageId
     * @return object fluent interface
     */
    public function setPageId($pageId = 0);

    /**
     * Set widget connection id
     *
     * @param integer $widgetId
     * @return object fluent interface
     */
    public function setWidgetConnectionId($widgetId);

    /**
     * Set widget position
     *
     * @param string $position
     * @return object fluent interface
     */
    public function setWidgetPosition($position);
}
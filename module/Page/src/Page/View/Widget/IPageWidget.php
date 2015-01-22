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
     * Is widget redirected
     *
     * @return boolean
     */
    public function isWidgetRedirected();

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

    /**
     * Get widget connection url
     *
     * @return string
     */
    public function getWidgetConnectionUrl();
}
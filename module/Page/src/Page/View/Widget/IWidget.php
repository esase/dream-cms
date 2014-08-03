<?php

interface IWidget {
    /**
     * Get widget content
     *
     * @return string
     */
    public function getContent();

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set current page id
     *
     * @param integer $pageId
     * @return object fluent interface
     */
    public function setPageId($pageId);

    /**
     * Set widget connection id
     *
     * @param integer $widgetId
     * @return object fluent interface
     */
    public function setWidgetConnectionId($widgetId);
}
<?php
namespace Page\View\Widget;
 
class PageHtmlWidget extends PageAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getWidgetSetting('content');
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->getWidgetSetting('title');
    }
}
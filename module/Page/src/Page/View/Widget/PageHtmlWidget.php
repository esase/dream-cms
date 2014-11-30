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
        if (null != ($content = trim($this->getWidgetSetting('page_html_content')))) {
            return $content;
        }

        return false;
    }
}
<?php
namespace Page\View\Widget;
 
class PageHtmlWidget extends AbstractWidget
{
    /**
     * Get widget content
     *
     * @return string
     */
    public function getContent() 
    {
        return $this->getSetting('content');
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->getSetting('title');
    }
}
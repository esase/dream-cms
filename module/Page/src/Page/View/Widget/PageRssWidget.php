<?php

namespace Page\View\Widget;
 
class PageRssWidget extends PageAbstractWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->appendFile($this->getView()->layoutAsset('jquery.rss.js'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (null != ($rssUrl = trim($this->getWidgetSetting('page_rss_url')))) {
            $wrapperId = 'rss-list-' . $this->widgetConnectionId;

            return $this->getView()->partial('page/widget/rss', [
                'wrapper_id' => $wrapperId,
                'url' => $rssUrl,
                'limit' => (int) $this->getWidgetSetting('page_rss_limit'),
                'show_description' => (int) $this->getWidgetSetting('page_rss_show_desc') ? true : false,
                'short_description' => (int) $this->getWidgetSetting('page_rss_use_short_desc') ? true : false
            ]);
        }

        return false;
    }
}
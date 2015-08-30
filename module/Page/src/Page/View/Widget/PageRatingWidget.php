<?php

namespace Page\View\Widget;
 
class PageRatingWidget extends PageAbstractWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('jquery.rateit.js'));

        $this->getView()->layoutHeadLink()->
                appendStylesheet($this->getView()->layoutAsset('jquery.rateit.css', 'css'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getView()->partial('page/widget/rating', [
        ]);
    }
}
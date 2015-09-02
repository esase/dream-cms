<?php

namespace Page\View\Widget;

use Page\Service\Page as PageService;
use Acl\Service\Acl as AclService;

class PageRatingWidget extends PageAbstractWidget
{
    /**
     * Max rating value
     */
    const MAX_RATING_VALUE = 5;

    /**
     * Model instance
     * @var \Page\Model\PageBase
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Page\Model\PageBase
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase');
        }

        return $this->model;
    }

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
        $disableRating = !AclService::checkPermission('pages_use_rating')
                || $this->getModel()->isPageRated($this->pageId, $this->getPageSlug());

        // process actions
        if ($this->getRequest()->isPost()) {
            if (false !== ($action = $this->
                    getRequest()->getPost('widget_action', false)) && $this->getRequest()->isXmlHttpRequest()) {

                switch ($action) {
                    case 'add_rating' :
                        return $this->getView()->json($this->addPageRating($disableRating));

                    default :
                }
            }
        }

        // get current page's rating info
        $pageRating = $this->getModel()->getPageRatingInfo($this->pageId, $this->getPageSlug());
        $currentRating = $pageRating
            ? $this->processRatingValue($pageRating['total_rating'] / $pageRating['total_count'])
            : 0;

        return $this->getView()->partial('page/widget/rating', [
            'rating' => $currentRating,
            'widget_url' => $this->getWidgetConnectionUrl(),
            'big_rating' => $this->getWidgetSetting('page_rating_size') == 'big_rating',
            'step_rating' => (float) $this->getWidgetSetting('page_rating_min_step'),
            'disable_rating' => $disableRating
        ]);
    }

    /**
     * Add page rating
     *
     * @param boolean $disableRating
     * @return array
     */
    protected function addPageRating($disableRating)
    {
        $ratingValue = (float) $this->getRequest()->getPost('value');

        if (!$disableRating && $ratingValue > 0 && $ratingValue <= self::MAX_RATING_VALUE) {
            // add rating
            $value = $this->getModel()->addPageRating($this->
                    pageId, $this->widgetConnectionId, $ratingValue, $this->getPageSlug());

            if (!is_string($value)) {
                return [
                    'value' => $this->processRatingValue($value),
                    'status' => 'success'
                ];
            }
        }

        return [
            'message' => $this->translate('Error occurred'),
            'status' => 'error'
        ];
    }

    /**
     * Process rating value
     *
     * @param float $value
     * @return string
     */
    protected function processRatingValue($value)
    {
        return number_format($value, 1);
    }

    /**
     * Get page slug
     *
     * @return string|integer
     */
    protected function getPageSlug()
    {
        return !empty(PageService::getCurrentPage()['pages_provider']) ? $this->getSlug() : null;
    }
}
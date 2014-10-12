<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageBreadcrumb extends AbstractHelper
{
    /**
     * Breadcrumb
     * @var array
     */
    protected $breadcrumb = [];

    /**
     * Page
     * @var array
     */
    protected $page;

    /**
     * Current page title
     * @var string
     */
    protected $currentPageTitle = null;

    /**
     * Page breadcrumb
     *
     * @return object - fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Set breadcrumb
     *
     * @param array $breadcrumb
     * @return object - fluent interface
     */
    public function setBreadcrumb(array $breadcrumb)
    {
        $this->breadcrumb = $breadcrumb;
        return $this;
    }

    /**
     * Set page
     *
     * @param array $page
     * @return object - fluent interface
     */
    public function setPage(array $page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Set current page title
     *
     * @param string $title
     * @return object - fluent interface
     */
    public function setCurrentPageTitle($title)
    {
        $this->currentPageTitle = $title;
        return $this;
    }

    /**
     * Get breadcrumb
     *
     * @return string
     */
    public function getBreadcrumb()
    {
        if ($this->breadcrumb && $this->page['level'] > 1) {
            $processedBreadcrumb = [];
            $pageUrl = null;

            // process breadcrumb
            foreach($this->breadcrumb as $index => $page) {
                $lastPage = !isset($this->breadcrumb[$index + 1]);
                $pageUrl .= $page['slug'];

                // process page's title
                $pageTitle = $this->currentPageTitle && $lastPage
                    ? $this->currentPageTitle
                    : $this->getView()->pageTitle($page);

                $processedBreadcrumb[] = [
                    'url' => !$lastPage ? $pageUrl : null,
                    'title' => $pageTitle
                ];

                $pageUrl .= '/';
            }

            // set current page title from breadcrumb
            $pageTitle = array_reverse($processedBreadcrumb);
            foreach ($pageTitle as $page) {
                $this->getView()->headTitle(strip_tags($page['title']));
            }

            return $this->getView()->partial('page/partial/breadcrumb', [
                'breadcrumb' => $processedBreadcrumb
            ]);
        }
    }
}
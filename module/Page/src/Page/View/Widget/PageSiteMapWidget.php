<?php
namespace Page\View\Widget;

use Localization\Service\Localization as LocalizationService;
use Page\Model\Page as PageModel;

class PageSiteMapWidget extends PageAbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Sitemap
     * @var string
     */
    protected $sitemap = null;

    /**
     * Get model
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
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getView()->partial('page/widget/sitemap', [
            'sitemap' => $this->getSitemap()
        ]);
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
       return $this->translate('Sitemap');
    }

    /**
     * Get sitemap
     *
     * @return string
     */
    protected function getSitemap()
    {
        if (null === $this->sitemap) {
            // process sitemap
            $this->sitemap = $this->processSitemapItems($this->
                    getModel()->getPagesTree(LocalizationService::getCurrentLocalization()['language']));
        }

        return $this->sitemap;
    }

    /**
     * Process sitemap items
     *
     * @param array $pages
     * @return string
     */
    protected function processSitemapItems(array $pages)
    {
        $sitemap = null;

        // process sitemap items
        foreach ($pages as $pageName => $pageOptions) {
            if ($pageOptions['site_map'] == PageModel::PAGE_IN_SITEMAP) {
                // get a page url
                if (false !== ($pageUrl = $this->getView()->pageUrl($pageName))) {
                    $sitemap .= $this->getView()->partial('page/widget/sitemap-item-start', [
                        'url' => $pageUrl,
                        'title' => PageModel::PAGE_TYPE_SYSTEM  
                            ? $this->getView()->translate($pageOptions['system_title']) 
                            : $this->getView()->escapeHtml($pageOptions['title'])
                    ]);

                    // check for children
                    if (!empty($pageOptions['children'])) {
                        if (null !== ($children = $this->processSitemapItems($pageOptions['children']))) {
                            $sitemap .= $this->getView()->partial('page/widget/sitemap-item-children', [
                                'children' => $children,
                            ]);
                        }
                    }

                    $sitemap .= $this->getView()->partial('page/widget/sitemap-item-end');
                }
            }
        }

        return $sitemap;
    }
}
<?php
namespace Page\View\Widget;

use Localization\Service\Localization as LocalizationService;
use Page\Model\PageNestedSet;
use Page\Utility\PageProvider as PageProviderUtility;

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
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->appendFile($this->getView()->layoutAsset('jquery.treeview.js'));

        $cssFile = $this->getView()->localization()->isCurrentLanguageLtr() 
            ? 'jquery.treeview.css' 
            : 'jquery.treeview.rtl.css';

        $this->getView()->layoutHeadLink()->appendStylesheet($this->getView()->layoutAsset($cssFile, 'css'));
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
     * Get sitemap
     *
     * @return string
     */
    protected function getSitemap()
    {
        if (null === $this->sitemap) {
            // process sitemap
            $this->sitemap = $this->processSitemapItems($this->
                    getModel()->getPagesTree($this->getCurrentLanguage()));
        }

        return $this->sitemap;
    }

    /**
     * Get current language
     *
     * @return string
     */
    protected function getCurrentLanguage()
    {
        return LocalizationService::getCurrentLocalization()['language'];
    }

    /**
     * Process dynamic pages
     *
     * @param array $pageOptions
     * @param array $dynamicPages
     * @return array
     */
    protected function processDynamicPages(array $pageOptions, array $dynamicPages)
    {
        $sitemap = null;

        foreach ($dynamicPages as $dynamicPage) {
            // check received params
            if (!isset($dynamicPage['url_params'], $dynamicPage['url_title'])) {
                continue;
            }

            if (false !== ($pageUrl = $this->getView()->
                    pageUrl($pageOptions['slug'], [], $this->getCurrentLanguage(), true))) {

                $sitemap .= $this->getView()->partial('page/widget/sitemap-item-start', [
                    'url' => $pageUrl,
                    'title' => $dynamicPage['url_title'],
                    'params' => $dynamicPage['url_params']
                ]);

                // check for children
                if (!empty($dynamicPage['children'])) {
                    if (null !== ($children =
                            $this->processDynamicPages($pageOptions, $dynamicPage['children']))) {

                        $sitemap .= $this->getView()->partial('page/widget/sitemap-item-children', [
                            'children' => $children
                        ]);
                    }
                }

                $sitemap .= $this->getView()->partial('page/widget/sitemap-item-end');
            }
        }

        return $sitemap;
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
            if ($pageOptions['site_map'] == PageNestedSet::PAGE_IN_SITEMAP) {
                // get dynamic pages
                if (!empty($pageOptions['pages_provider'])) {
                    if (null != ($dynamicPages =
                            PageProviderUtility::getPages($pageOptions['pages_provider'], $this->getCurrentLanguage()))) {

                        $sitemap .= $this->processDynamicPages($pageOptions, $dynamicPages);
                    }
                }
                else {
                    // get a page url
                    if (false !== ($pageUrl = $this->getView()->pageUrl($pageName))) {
                        $sitemap .= $this->getView()->partial('page/widget/sitemap-item-start', [
                            'url' => $pageUrl,
                            'title' => $this->getView()->pageTitle($pageOptions),
                            'params' => []
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
        }

        return $sitemap;
    }
}
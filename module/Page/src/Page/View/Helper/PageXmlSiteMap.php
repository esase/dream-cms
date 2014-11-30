<?php
namespace Page\View\Helper;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\Utility\PageProvider as PageProviderUtility;
use Page\Model\PageNestedSet;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation;

class PageXmlSiteMap extends AbstractHelper
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase');
        }

        return $this->model;
    }

    /**
     * Page xml sitemap
     * 
     * @return object Zend\Navigation\Navigation
     */
    public function __invoke()
    {
        $xmlMap = [];

        if (null != ($siteMap = $this->getModel()->getAllPagesMap())) {
            $processedSiteMap = [];
            foreach($siteMap as $language => $pages) {
                foreach($pages as $pageOptions) {
                    if ($pageOptions['xml_map'] == PageNestedSet::PAGE_IN_XML_MAP) {
                        // get dynamic pages
                        if (!empty($pageOptions['pages_provider'])) {
                            if (null != ($dynamicPages =
                                    PageProviderUtility::getPages($pageOptions['pages_provider'], $language))) {

                                $xmlMap = array_merge($xmlMap,
                                        $this->processDynamicPages($pageOptions, $dynamicPages, $language));
                            }
                        }
                        else {
                            // get a page url
                            if (false !== ($pageUrl = $this->getView()->pageUrl($pageOptions['slug'], [], $language))) {
                                $xmlMap[] = [
                                    'uri' => $this->getView()->
                                            url('page', ['language' => $language, 'page_name' => $pageUrl], ['force_canonical' => true]),

                                    'lastmod' => $pageOptions['date_edited'],
                                    'changefreq' => $pageOptions['xml_map_update'],
                                    'priority' => $pageOptions['xml_map_priority']
                                ];
                            }
                        }
                    }
                }
            }
        }

        return new Navigation($xmlMap);
    }

    /**
     * Process dynamic pages
     *
     * @param array $pageOptions
     * @param array $dynamicPages
     * @param string $language
     * @return array
     */
    protected function processDynamicPages(array $pageOptions, array $dynamicPages, $language)
    {
        $xmlMap = [];

        foreach ($dynamicPages as $dynamicPage) {
            // check received params
            if (!isset($dynamicPage['url_params'], $dynamicPage['xml_map'])) {
                continue;
            }

            if (false !== ($pageUrl = $this->getView()->pageUrl($pageOptions['slug'], [], $language, true))) {
                $pageUrl = $this->getView()->url('page', ['language' =>
                        $language, 'page_name' => $pageUrl] + $dynamicPage['url_params'], ['force_canonical' => true]);

                $lastmod = empty($dynamicPage['xml_map']['lastmod'])
                    ? $pageOptions['date_edited']
                    : $dynamicPage['xml_map']['lastmod'];

                $changefreq = empty($dynamicPage['xml_map']['changefreq'])
                    ? $pageOptions['xml_map_update']
                    : $dynamicPage['xml_map']['changefreq'];

                $priority = empty($dynamicPage['xml_map']['priority'])
                    ? $pageOptions['xml_map_priority']
                    : $dynamicPage['xml_map']['priority'];

                $xmlMap[] = [
                    'uri' => $pageUrl,
                    'lastmod' => $lastmod,
                    'changefreq' => $changefreq,
                    'priority' => $priority
                ];

                // check for children
                if (!empty($dynamicPage['children'])) {
                    $xmlMap = array_merge($xmlMap, $this->
                            processDynamicPages($pageOptions, $dynamicPage['children'], $language));
                }
            }
        }

        return $xmlMap;
    }
}
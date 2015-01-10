<?php
namespace Page\View\Widget;

use Localization\Service\Localization as LocalizationService;
use Page\Utility\PageProvider as PageProviderUtility;
use Page\Service\Page as PageService;

class PageSidebarMenuWidget extends PageAbstractWidget
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
        if (null != ($currentPage = PageService::getCurrentPage())) {
            $menuType = $this->getWidgetSetting('page_sidebar_menu_type');
            $showDynamicPages = (int) $this->getWidgetSetting('page_sidebar_menu_show_dynamic');
            $currentLanguage = LocalizationService::getCurrentLocalization()['language'];
            $pages = [];

            // collect sidebar menu items
            foreach ($this->getModel()->getPagesMap($currentLanguage) as $page) {
                // check the type of menu
                if ($page['parent'] != ($menuType ==
                        'sidebar_menu_subpages' ? $currentPage['slug'] : $currentPage['parent_slug'])) {

                    continue;
                }

                // get dynamic pages
                if (!empty($page['pages_provider'])) {
                    if ($showDynamicPages) {
                        if (null != ($dynamicPages =
                                PageProviderUtility::getPages($page['pages_provider'], $currentLanguage))) {

                            // process only the first pages level
                            foreach ($dynamicPages as $dynamicPage) {
                                // check received params
                                if (!isset($dynamicPage['url_params'], $dynamicPage['url_title'])) {
                                    continue;
                                }

                                if (false !== ($pageUrl = $this->
                                        getView()->pageUrl($page['slug'], [], $currentLanguage, true))) {

                                    $pages[] = [
                                        'active' => !empty($dynamicPage['url_active']),
                                        'url' => $pageUrl,
                                        'title' => $dynamicPage['url_title'],
                                        'params' => $dynamicPage['url_params']
                                    ];
                                }
                            }
                        }
                    }
                }
                else {
                    // get a page url
                    if (false === ($pageUrl = $this->getView()->pageUrl($page['slug']))) {
                        continue;
                    }

                    $pages[] = [
                        'active' => $currentPage['slug'] == $page['slug'],
                        'url' => $pageUrl,
                        'title' => $this->getView()->pageTitle($page)
                    ];
                }
            }

            if ($pages) {
                return $this->getView()->partial('page/widget/sidebar-menu', [
                    'pages' => $pages
                ]);
            }
        }

        return false;
    }
}
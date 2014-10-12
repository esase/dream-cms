<?php
namespace Page\View\Helper;

use Page\Model\Page as PageModel;
use Zend\View\Helper\AbstractHelper;

class PageMenu extends AbstractHelper
{
    /**
     * Pages tree
     * @var array
     */
    protected $pagesTree = [];

    /**
     * Class constructor
     *
     * @param array $pagesTree
     */
    public function __construct(array $pagesTree = [])
    {
        $this->pagesTree = $pagesTree;
    }

    /**
     * Page menu
     *
     * @return string
     */
    public function __invoke()
    {
        if (null !== ($menuItems = $this->processMenuItems($this->pagesTree))) {
            return $this->getView()->partial('page/partial/menu', [
                'menu_items' => $this->processMenuItems($this->pagesTree)
            ]);
        }
    }

    /**
     * Process menu items
     *
     * @param array $pages
     * @return string
     */
    protected function processMenuItems(array $pages)
    {
        $menu = null;

        // process menu items
        foreach ($pages as $pageName => $pageOptions) {
            if ($pageOptions['menu'] == PageModel::PAGE_IN_MENU) {
                // get a page url
                if (false !== ($pageUrl = $this->getView()->pageUrl($pageName))) {
                    // skip the home page 
                    if ($pageOptions['level'] == 1) {
                        if (!empty($pageOptions['children'])) {
                            $menu = $this->processMenuItems($pageOptions['children']);
                        }
                    }
                    else {
                        $menu .= $this->getView()->partial('page/partial/menu-item-start', [
                            'url' => $pageUrl,
                            'title' => $this->getView()->pageTitle($pageOptions)
                        ]);

                        // check for children
                        if (!empty($pageOptions['children'])) {
                            if (null !== ($children = $this->processMenuItems($pageOptions['children']))) {
                                $menu .= $this->getView()->partial('page/partial/menu-item-children', [
                                    'children' => $children,
                                ]);
                            }
                        }

                        $menu .= $this->getView()->partial('page/partial/menu-item-end');
                    }
                }
            }
        }

        return $menu;
    }
}
<?php
namespace Page\View\Helper;

use Page\Service\Page as PageService;
use Page\Model\PageNestedSet;
use Zend\View\Helper\AbstractHelper;

class PageMenu extends AbstractHelper
{
    /**
     * Pages tree
     * @var array
     */
    protected $pagesTree = [];

    /**
     * Max level
     * @var integer
     */
    protected $maxLevel = 0;

    /**
     * Current page
     * @var array
     */
    protected $currentPage;

    /**
     * Class constructor
     *
     * @param array $pagesTree
     */
    public function __construct(array $pagesTree = [])
    {
        $this->pagesTree = $pagesTree;
        $this->currentPage = PageService::getCurrentPage();
    }

    /**
     * Page menu
     *
     * @param integer $maxLevel
     * @return string
     */
    public function __invoke($maxLevel = 0)
    {
        $this->maxLevel = $maxLevel;

        return $this->getView()->partial('page/partial/menu', [
            'menu_items' => $this->processMenuItems($this->pagesTree)
        ]);
    }

    /**
     * Process menu items
     *
     * @param array $pages
     * @param integer $level
     * @return string
     */
    protected function processMenuItems(array $pages, $level = 1)
    {
        $menu = null;

        // process menu items
        foreach ($pages as $pageName => $pageOptions) {
            if ($pageOptions['menu'] == PageNestedSet::PAGE_IN_MENU) {
                // get a page url
                if (false !== ($pageUrl = $this->getView()->pageUrl($pageName))) {
                    // skip the home page 
                    if ($pageOptions['level'] == 1) {
                        if (!empty($pageOptions['children'])) {
                            $menu = $this->processMenuItems($pageOptions['children']);
                        }
                    }
                    else {
                        $childrenMenu = null;

                        // check for children
                        if (!empty($pageOptions['children']) && (!$this->maxLevel || $level < $this->maxLevel)) {
                            if (null !== ($children = $this->processMenuItems($pageOptions['children'], ($level + 1)))) {
                                $childrenMenu = $this->getView()->partial('page/partial/menu-item-children', [
                                    'children' => $children
                                ]);
                            }
                        }

                        $menu .= $this->getView()->partial('page/partial/menu-item-start', [
                            'url' => $pageUrl,
                            'title' => $this->getView()->pageTitle($pageOptions),
                            'children' => $childrenMenu !== null,
                            'is_sub_item' => $level > 1,
                            'active' => $this->currentPage['slug'] == $pageOptions['slug']
                        ]);


                        if ($childrenMenu) {
                            $menu .= $childrenMenu;
                        }

                        $menu .= $this->getView()->partial('page/partial/menu-item-end');
                    }
                }
            }
        }

        return $menu;
    }
}
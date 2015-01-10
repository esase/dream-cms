<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTree extends AbstractHelper
{
    /**
     * Pages
     * @var array
     */
    protected $pages = [];

    /**
     * Tree
     * @var string
     */
    protected $tree = null;

    /**
     * Active page id
     * @var integer
     */
    protected $activePageId;

    /**
     * Tree cookie lifetime
     * @var integer
     */
    protected $treeCookieLifetimeDays = 30;

    /**
     * Filters
     * @var array
     */
    protected $filters = [];

    /**
     * Disabled tree
     * @var boolean
     */
    protected $disabledTree;

    /**
     * Use slug
     * @var boolean
     */
    protected $useSlug = false;

    /**
     * Class constructor
     *
     * @param array $pagesTree
     */
    public function __construct(array $pages = [])
    {
        $this->pages = $pages;
    }

    /**
     * Page tree
     *
     * @param string $treeId
     * @param integer $activePageId
     * @param boolean $addRootPage
     * @param array $filters
     * @param boolean $disabledTree
     * @param boolean $useSlug
     * @return string
     */
    public function __invoke($treeId, $activePageId = null, $addRootPage = true, array $filters = [], $disabledTree = false, $useSlug = false)
    {
        $this->activePageId = $activePageId;
        $this->filters = $filters;
        $this->disabledTree = $disabledTree;
        $this->useSlug = $useSlug;

        if (!$this->pages) {
            return $this->getView()->partial('page/partial/page-tree-empty');    
        }

        if ($addRootPage) {
            // add a root page
            $rootPage['site'] = [
                'id' => null,
                'system_title' => 'Site',
                'type' => 'system',
                'children' => $this->pages
            ];

            $this->pages = $rootPage;
        }

        return $this->getView()->partial('page/partial/page-tree', [
            'tree_id' => $treeId,
            'tree' => $this->getTree(),
            'cookie_lifetime' => $this->treeCookieLifetimeDays
        ]);
    }

    /**
     * Get tree
     *
     * @return string
     */
    protected function getTree()
    {
        if (null === $this->tree) {
            // process pages tree
            $this->tree = $this->processTreeItems($this->pages);
        }

        return $this->tree;
    }

    /**
     * Process tree items
     *
     * @param array $pages
     * @return string
     */
    protected function processTreeItems(array $pages)
    {
        $tree = null;

        // process tree items
        foreach ($pages as $pageName => $pageOptions) {
            $tree .= $this->getView()->partial('page/partial/page-tree-item-start', [
                'page_id' => $pageOptions['id'],
                'active' => $this->activePageId == $pageOptions['id'],
                'title' => $this->getView()->pageTitle($pageOptions),
                'filters' => $this->filters,
                'disabled_tree' => $this->disabledTree,
                'use_slug' => $this->useSlug
            ]);

            // check for children
            if (!empty($pageOptions['children'])) {
                if (null !== ($children = $this->processTreeItems($pageOptions['children']))) {
                    $tree .= $this->getView()->partial('page/partial/page-tree-item-children', [
                        'children' => $children
                    ]);
                }
            }

            $tree .= $this->getView()->partial('page/partial/page-tree-item-end');
        }

        return $tree;
    }
}
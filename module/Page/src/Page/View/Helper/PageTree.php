<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTree extends AbstractHelper
{
    /**
     * Pages
     *
     * @var array
     */
    protected $pages = [];

    /**
     * Tree
     *
     * @var string
     */
    protected $tree = null;

    /**
     * Active page id
     *
     * @var integer
     */
    protected $activePageId;

    /**
     * Tree cookie lifetime
     *
     * @var integer
     */
    protected $treeCookieLifetimeDays = 30;

    /**
     * Filters
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Disabled tree
     *
     * @var boolean
     */
    protected $disabledTree;

    /**
     * Use slug
     *
     * @var boolean
     */
    protected $useSlug = false;

    /**
     * Link description
     *
     * @var string
     */
    protected $linkDescription;

    /**
     * Class constructor
     *
     * @param array $pages
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
     * @param string $linkDescription
     * @return string
     */
    public function __invoke($treeId, $activePageId = null, $addRootPage = true,
            array $filters = [], $disabledTree = false, $useSlug = false, $linkDescription = null)
    {
        $this->activePageId = $activePageId;
        $this->filters = $filters;
        $this->disabledTree = $disabledTree;
        $this->useSlug = $useSlug;
        $this->linkDescription = $linkDescription;

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
                'use_slug' => $this->useSlug,
                'link_description' => $this->linkDescription
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
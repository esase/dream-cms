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

use Page\Service\Page as PageService;
use Page\Model\PageNestedSet;
use Zend\View\Helper\AbstractHelper;

class PageMenu extends AbstractHelper
{
    /**
     * Pages tree
     *
     * @var array
     */
    protected $pagesTree = [];

    /**
     * Max level
     *
     * @var integer
     */
    protected $maxLevel = 0;

    /**
     * Current page
     *
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
                            'active' => !empty($this->currentPage['slug']) && $this->currentPage['slug'] == $pageOptions['slug']
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
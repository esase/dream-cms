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
namespace Page\Model;

use Acl\Model\AclBase as AclBaseModel;
use Application\Model\ApplicationAbstractNestedSet;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression as Expression;

class PageNestedSet extends ApplicationAbstractNestedSet 
{
    /**
     * Page status active
     */
    const PAGE_STATUS_ACTIVE = 1;

    /**
     * Page in menu
     */
    const PAGE_IN_MENU = 1;

    /**
     * Page in site map
     */
    const PAGE_IN_SITEMAP = 1;

    /**
     * Page in xml map
     */
    const PAGE_IN_XML_MAP = 1;

    /**
     * Page in footer menu
     */
    const PAGE_IN_FOOTER_MENU = 1;

    /**
     * Page in user menu
     */
    const PAGE_IN_USER_MENU = 1;

    /**
     * Page type system
     */
    const PAGE_TYPE_SYSTEM = 'system';

    /**
     * Page type custom
     */
    const PAGE_TYPE_CUSTOM = 'custom';

    /**
     * Layout path
     *
     * @var string
     */
    protected $layoutPath = 'page/layout-page/';

    /**
     * Manage layout path
     *
     * @var string
     */
    protected $manageLayoutPath = 'page/manage-layout-page/';

    /**
     * Class constructor
     *
     * @param \Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        parent::__construct($tableGateway);
    }

    /**
     * Get layout path
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return $this->layoutPath;
    }

    /**
     * Get manage layout path
     *
     * @return string
     */
    public function getManageLayoutPath()
    {
        return $this->manageLayoutPath;
    }

    /**
     * Move page
     *
     * @param array $page
     * @param array $parent
     * @param string $language
     * @param integer $nearKey
     * @param string $pageDirection
     * @return boolean|string
     */
    public function movePage($page, $parent, $language, $nearKey = null, $pageDirection = null)
    {
        $filter = [
            'language' =>  $language
        ];

        $options = [
            'id' => $page['id'],
            'left_key' =>  $page['left_key'],
            'right_key' => $page['right_key'],
            'level' => $page['level'],
            'parent_id' => $parent['id'],
            'parent_left_key' => $parent['left_key'],
            'parent_right_key' => $parent['right_key'],
            'parent_level' => $parent['level']
        ];

        if ($nearKey && false !== ($nearNode = $this->getNodeInfo($nearKey))) {
            switch ($pageDirection) {
                case 'after' :
                    $options = array_merge($options, [
                        'after_right_key' => $nearNode[$this->right]
                    ]);

                    return $this->moveNodeAfter($options, $filter, false);

                case 'before' :
                    $options = array_merge($options, [
                        'before_left_key' => $nearNode[$this->left]
                    ]);

                    return $this->moveNodeBefore($options, $filter, false);

                default :
                    return $this->moveNodeToEnd($options, $filter, false);
            }
        }

        return $this->moveNodeToEnd($options, $filter, false);
    }

    /**
     * Add page
     *
     * @param integer $level
     * @param integer $leftKey
     * @param integer $rightKey
     * @param array $page
     * @param string $language
     * @param integer $nearKey
     * @param string $pageDirection
     * @return integer|string
     */
    public function addPage($level, $leftKey, $rightKey, array $page, $language, $nearKey = null, $pageDirection = null)
    {
        $filter = [
            'language' =>  $language
        ];

        if ($nearKey && false !== ($nearNode = $this->getNodeInfo($nearKey))) {
            switch ($pageDirection) {
                case 'after' :
                    return $this->insertNodeAfter($level, $nearNode[$this->right], $page, $filter, false);

                case 'before' :
                    return $this->insertNodeBefore($level, $leftKey, $nearNode[$this->left], $page, $filter, false);

                default :
                    return $this->insertNodeToEnd($level, $rightKey, $page, $filter, false);
            }
        }

        return $this->insertNodeToEnd($level, $rightKey, $page, $filter, false);
    }

    /**
     * Delete page
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param string $language
     * @return boolean|string
     */
    public function deletePage($leftKey, $rightKey, $language)
    {
        return $this->deleteNode($leftKey, $rightKey, ['language' => $language]);
    }

    /**
     * Get active page parents
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $userRole
     * @param string $language
     * @param boolean $excludeHome
     * @return array|false
     */
    public function getActivePageParents($leftKey, $rightKey, $userRole, $language, $excludeHome = true)
    {
        return $this->getParentNodes($leftKey, 
                $rightKey, [], function (Select $select) use ($userRole, $language, $excludeHome) {

            $select->columns(['slug', 'title', 'type', 'level', 'redirect_url']);
            $select = $this->getPageActiveFilter($select, $userRole, $language, false);

            if ($excludeHome) {
                $select->where->notEqualTo($this->tableGateway->table . '.level', 1);
            }
        });
    }

    /**
     * Get all page children
     *
     * @param integer $parentId
     * @param boolean $active
     * @return array|false
     */
    public function getAllPageChildren($parentId, $active)
    {
        return $this->getChildrenNodes($parentId, [], function (Select $select) use ($active) {
            $select->join(
                ['b' => 'page_system'],
                'b.id = ' . $this->tableGateway->table . '.system_page', 
                [
                    'privacy',
                    'system_title' => 'title'
                ],
                'left'
            );

            if ($active) {
                $select->join(
                    ['c' => 'application_module'],
                    new Expression('c.id = ' . $this->tableGateway->table . '.module and c.status = ?', [
                        ApplicationAbstractBase::MODULE_STATUS_ACTIVE
                    ]),
                    []
                );
            }
        });
    }

    /**
     * Get active page info
     *
     * @param string $slug
     * @param integer $userRole
     * @param string $language
     * @return array|false
     */
    public function getActivePageInfo($slug, $userRole, $language)
    {
        return $this->getNodeInfo($slug, 'slug', function (Select $select) use ($userRole, $language) {
            $select = $this->getPageActiveFilter($select, $userRole, $language);
        });
    }

    /**
     * Get page active filter
     *
     * @param \Zend\Db\Sql\Select $select
     * @param integer $userRole
     * @param string $language
     * @param boolean $addLayout
     * @return \Zend\Db\Sql\Select
     */
    protected function getPageActiveFilter(Select $select, $userRole, $language, $addLayout = true)
    {
        $select->join(
            ['b' => 'application_module'],
            new Expression('b.id = module and b.status = ?', [ApplicationAbstractBase::MODULE_STATUS_ACTIVE]), 
            []
        );

        // add a layout information
        if ($addLayout) {
            $select->join(
                ['c' => 'page_layout'],
                'c.id = ' . $this->tableGateway->table . '.layout', 
                [
                    'layout' => 'name'
                ]
            );
        }

        // administrators can see any pages
        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->join(
                ['d' => 'page_visibility'],
                new Expression('d.page_id = ' . 
                        $this->tableGateway->table . '.id and d.hidden = ?', [$userRole]), 
                [],
                'left'
            );
        }

        $select->join(
            ['i' => 'page_system'],
            'i.id = ' . $this->tableGateway->table . '.system_page', 
            [
                'privacy',
                'pages_provider',
                'dynamic_page',
                'system_title' => 'title'
            ],
            'left'
        );

        $select->join(
            ['f' => 'page_structure'],
            'f.id = ' . $this->tableGateway->table . '.parent_id', 
            [
                'parent_slug' => 'slug'
            ],
            'left'
        );

        $select->where([
            $this->tableGateway->table  . '.language' => $language,
            $this->tableGateway->table  . '.active' => self::PAGE_STATUS_ACTIVE
        ]);

        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->where->IsNull('d.id');
        }
        
        return $select;
    }
}
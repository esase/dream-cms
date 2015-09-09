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
namespace User\Model;

use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Predicate\Like as LikePredicate;

class UserAdministration extends UserBase
{
    /**
     * Get users
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string nickname
     *      string email
     *      string status
     *      integer role
     * @return \Zend\Paginator\Paginator
     */
    public function getUsers($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'nickname',
            'email',
            'registered',
            'status'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'user_list'])
            ->columns([
                'id' => 'user_id',
                'nickname' => 'nick_name',
                'email',
                'status',
                'registered',
                'role_id' => 'role'
            ])
            ->join(
                ['b' => 'acl_role'],
                'a.role = b.id',
                [
                    'role' => 'name'
                ]
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by nickname
        if (!empty($filters['nickname'])) {
            $select->where([
                new LikePredicate('nick_name', $filters['nickname'] . '%')
            ]);
        }

        // filter by email
        if (!empty($filters['email'])) {
            $select->where([
                'email' => $filters['email']
            ]);
        }

        // filter by status
        if (!empty($filters['status'])) {
            $select->where([
                'status' => $filters['status']
            ]);
        }

        // filter by role
        if (!empty($filters['role'])) {
            $select->where([
                'role' => $filters['role']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}
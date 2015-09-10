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
namespace XmlRpc\Model;

use Application\Utility\ApplicationCache as CacheUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class XmlRpc extends XmlRpcBase
{
    /**
     * Get all registered classes
     *
     * @return array
     */
    public function getClasses()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_XMLRPC_CLASSES);

        // check data in cache
        if (null === ($classes = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(['a' =>'xmlrpc_class'])
                ->columns([
                    'namespace',
                    'path',
                    'module'
                ])
                ->join(
                    ['b' => 'application_module'],
                    new Expression('a.module = b.id and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                    []
                );

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $classes = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $classes);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_XMLRPC_DATA_TAG]);
        }

        return $classes;
    }
}
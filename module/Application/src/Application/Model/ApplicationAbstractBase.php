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
namespace Application\Model;

use Application\Service\Application as ApplicationService;
use Application\Exception\ApplicationException;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Utility\ApplicationCache as CacheUtility;
use Localization\Service\Localization as LocalizationService;
use Application\Utility\ApplicationSlug as SlugUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Cache\Storage\StorageInterface;
use ZipArchive;

abstract class ApplicationAbstractBase extends Sql
{
    /**
     * Module by name
     */
    const CACHE_MODULE_BY_NAME = 'Application_Module_By_Name_';

    /**
     * Active modules
     */
    const CACHE_MODULES_ACTIVE = 'Application_Modules_Active';

    /**
     * Modules data cache tag
     */
    const CACHE_MODULES_DATA_TAG = 'Application_Modules_Data_Tag';

    /**
     * Module active status flag
     */
    const MODULE_STATUS_ACTIVE = 'active';

    /**
     * Module not active status flag
     */
    const MODULE_STATUS_NOT_ACTIVE = 'not_active';

    /**
     * Module type system
     */
    const MODULE_TYPE_SYSTEM = 'system';

    /**
     * Module type custom
     */
    const MODULE_TYPE_CUSTOM = 'custom';

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Static cache instance
     *
     * @var \Zend\Cache\Storage\StorageInterface
     */
    protected $staticCacheInstance;

    /**
     * Slug length
     *
     * @var integer
     */
    protected $slugLength = 10;

    /**
     * Class constructor
     *
     * @param \Zend\Db\Adapter\AdapterInterface $adapter
     * @param \Zend\Cache\Storage\StorageInterface $staticCacheInstance
     */
    public function __construct(AdapterInterface $adapter, StorageInterface $staticCacheInstance)
    {
        parent::__construct($adapter);

        $this->serviceLocator = ServiceLocatorService::getServiceLocator();
        $this->staticCacheInstance = $staticCacheInstance;
    }

    /**
     * Get current language
     *
     * @return string
     */
    public function getCurrentLanguage()
    {
       return LocalizationService::getCurrentLocalization()['language']; 
    }

    /**
     * Get date range
     *
     * @param string $date 
     * @return array
     */
    public function getDateRange($date)
    {
        return [
            strtotime($date),
            strtotime($date . ' 23:59:59')
        ];
    }

    /**
     * Generate slug
     *
     * @param integer $objectId
     * @param string $title
     * @param string $table
     * @param string $idField
     * @param integer $slugLength
     * @param array $filters
     * @param string $slugField
     * @param string $spaceDivider
     * @return string
     */
    public function generateSlug($objectId, $title, $table, $idField, $slugLength = 60, array $filters = [], $slugField = 'slug', $spaceDivider = '-')
    {
        // generate a slug
        $newSlug  = $slug = SlugUtility::slugify($title, $slugLength, $spaceDivider);
        $slagSalt = null;

        while (true) {
            // check the slug existent
            $select = $this->select();
            $select->from($table)
                ->columns([
                    $slugField
                ])
                ->where([
                    $slugField => $newSlug                    
                ] + $filters);

            $select->where([
                new NotInPredicate($idField, [$objectId])
            ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // generated slug not found
            if (!$resultSet->current()) {
                break;
            }
            else {
                $newSlug = $objectId . $spaceDivider . $slug . $slagSalt;
            }

            // add an extra slug
            $slagSalt = $spaceDivider . SlugUtility::generateRandomSlug($this->slugLength); // add a salt
        }

        return $newSlug;
    }

    /**
     * Get module info
     *
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo($moduleName)
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_MODULE_BY_NAME . $moduleName);

        // check data in cache
        if (null === ($module = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_module')
                ->columns([
                    'id',
                    'name',
                    'type',
                    'status',
                    'version',
                    'vendor',
                    'vendor_email',
                    'description',
                    'layout_path'
                ])
                ->where(['name' => $moduleName]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            if (null != ($module = $resultSet->current())) {
                // save data in cache
                $this->staticCacheInstance->setItem($cacheName, $module);
                $this->staticCacheInstance->setTags($cacheName, [self::CACHE_MODULES_DATA_TAG]);
            }
        }

        return $module;
    }

    /**
     * Get active modules list
     *
     * @return array
     */
    public function getActiveModulesList()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_MODULES_ACTIVE);

        // check data in cache
        if (null === ($modulesList = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_module')
                ->columns([
                    'id',
                    'name'
                ])
            ->where([
                'status' => self::MODULE_STATUS_ACTIVE
            ])
            ->order('id');
    
            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
    
            foreach ($resultSet as $module) {
                $modulesList[$module->id] = $module->name;
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $modulesList);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_MODULES_DATA_TAG]);
        }

        return $modulesList;
    }

    /**
     * Generate a rand string
     *
     * @param integer $length
     * @param  string $charList
     * @return string
     */
    public function generateRandString($length = 10, $charList = null)
    {
        return SlugUtility::generateRandomSlug($length, $charList);
    }

    /**
     * Get adapter
     *
     * @return \Zend\Db\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Generate tmp dir
     *
     * @return string
     */
    protected function generateTmpDir()
    {
        return ApplicationService::getTmpPath() . '/' . time() . '_' . $this->generateRandString();
    }

    /**
     * Unzip files
     *
     * @param string $file
     * @param string $path
     * @throws ApplicationException
     * @return void
     */
    public function unzipFiles($file, $path)
    {
        // unzip a custom module into the tmp dir
        $zip = new ZipArchive;

        if (true !== ($result = $zip->open($file))) {
            $zip->close();
            throw new ApplicationException('Cannot open archived files');
        }

        if (true !== ($result = $zip->extractTo($path))) {
            $zip->close();
            throw new ApplicationException('Cannot extract archived files');
        }

        $zip->close();
    }

    /**
     * Execute sql file
     *
     * @param string $filePath
     * @param array $replace
     *      string from
     *      string to
     * @throws ApplicationException
     * @return void
     */
    protected function executeSqlFile($filePath, array $replace = [])
    {
        if (!file_exists($filePath) || !($handler = fopen($filePath, 'r'))) {
            throw new ApplicationException('Sql file not found or permission denied');
        }

        $query = null;
        $delimiter = ';';

        // collect all queries
        while(!feof($handler)) {
            $str = trim(fgets($handler));

            if(empty($str) || $str[0] == '' || $str[0] == '#' || ($str[0] == '-' && $str[1] == '-'))
                continue;

            // change delimiter
            if(strpos($str, 'DELIMITER //') !== false || strpos($str, 'DELIMITER ;') !== false) {
                $delimiter = trim(str_replace('DELIMITER', '', $str));
                continue;
            }

            $query .= ' ' . $str;

            // check for multi line query
            if(substr($str, -strlen($delimiter)) != $delimiter) {
                continue;
            }

            // execute query
            if (!empty($replace['from']) && !empty($replace['to'])) {
                $query = str_replace($replace['from'], $replace['to'], $query);
            }

            if($delimiter != ';') {
                $query = str_replace($delimiter, '', $query);
            }

            $this->adapter->query(trim($query), DbAdapter::QUERY_MODE_EXECUTE);
            $query = null;
        }

        fclose($handler);
    }
}
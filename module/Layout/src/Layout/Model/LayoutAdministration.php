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
namespace Layout\Model;

use Layout\Event\LayoutEvent;
use Layout\Exception\LayoutException;
use Layout\Utility\LayoutCache as LayoutCacheUtility;
use Application\Utility\ApplicationFtp as ApplicationFtpUtility;
use Application\Utility\ApplicationFileSystem as ApplicationFileSystemUtility;
use Application\Utility\ApplicationErrorLogger;
use Application\Utility\ApplicationCache as ApplicationCacheUtility;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Service\Application as ApplicationService;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Predicate\Like as LikePredicate;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use DirectoryIterator;
use CallbackFilterIterator;
use Exception;

class LayoutAdministration extends LayoutBase
{
    /**
     * Layout install config
     *
     * @var string
     */
    protected $layoutInstallConfig = '/layout.config.install.php';

    /**
     * Is custom layout
     *
     * @param string $layout
     * @return boolean
     */
    public function isCustomLayout($layout)
    {
        $layoutDirectory = ApplicationService::getLayoutPath() . '/' . basename($layout);

        return file_exists($layoutDirectory . $this->layoutInstallConfig);
    }

    /**
     * Get custom layout install config
     *
     * @param string $layout
     * @param boolean $checkExisting
     * @return boolean|array
     */
    public function getCustomLayoutInstallConfig($layout, $checkExisting = true)
    {
        // is a custom layout
        if ($checkExisting && false === ($result = $this->isCustomLayout($layout))) {
            return $result;
        }

        return include ApplicationService::getLayoutPath() . '/' . basename($layout) . $this->layoutInstallConfig;
    }

    /**
     * Get not installed layouts
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return \Zend\Paginator\Paginator
     */
    public function getNotInstalledLayouts($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
            'name',
            'vendor',
            'vendor_email',
            'version',
            'date'
        ];

        $orderType = !$orderType || $orderType == 'asc'
            ? SORT_ASC
            : SORT_DESC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'name';

        // get installed active layouts
        $installedLayouts = array_map('strtolower', $this->getAllInstalledLayouts());

        // get all directories and files
        $directoryIterator = new DirectoryIterator(ApplicationService::getLayoutPath());
        $layouts = new CallbackFilterIterator($directoryIterator, function($current) use ($installedLayouts) {
            // skip already installed layouts
            if ($current->isDot() || !$current->isDir()
                    || in_array(strtolower($current->getFileName()), $installedLayouts)) {

                return false;
            }

            // check the layout
            return $this->isCustomLayout($current->getFileName());
        });

        $processedLayouts = [];
        $orderValues    = [];

        // process not installed layouts
        foreach($layouts as $layout) {
            $layoutInstallConfig = $this->getCustomLayoutInstallConfig($layout->getFileName(), false);

            $layoutInfo = [
                'name' => $layout->getFileName(),
                'vendor' => !empty($layoutInstallConfig['vendor']) ? $layoutInstallConfig['vendor'] : null,
                'email' => !empty($layoutInstallConfig['vendor_email']) ? $layoutInstallConfig['vendor_email'] : null,
                'version' => !empty($layoutInstallConfig['version']) ? $layoutInstallConfig['version'] : null,
                'date' => $layout->getMTime()
            ];

            $processedLayouts[] = $layoutInfo; 
            $orderValues[]      = $layoutInfo[$orderBy];
        }

        array_multisort($orderValues, $orderType, $processedLayouts);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedLayouts));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Clear layout caches
     *
     * @return void
     */
    public function clearLayoutCaches()
    {
        ApplicationCacheUtility::clearJsCache();
        ApplicationCacheUtility::clearCssCache();
        LayoutCacheUtility::clearLayoutCache();

        ApplicationCacheUtility::clearDynamicCache();
    }

    /**
     * Install custom layout
     *
     * @param string $layoutName
     * @param array $layoutInstallConfig
     *      string compatable
     *      string version 
     *      string vendor 
     *      string vendor_email 
     * @return boolean|string
     */
    public function installCustomLayout($layoutName, array $layoutInstallConfig)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $compatable = !empty($layoutInstallConfig['compatable'])
                ? trim($layoutInstallConfig['compatable'])
                : null;

            $version = !empty($layoutInstallConfig['version'])
                ? trim($layoutInstallConfig['version'])
                : null;

            $vendor = !empty($layoutInstallConfig['vendor'])
                ? trim($layoutInstallConfig['vendor'])
                : null;

            $vendorEmail = !empty($layoutInstallConfig['vendor_email'])
                ? trim($layoutInstallConfig['vendor_email'])
                : null;

            if (!$compatable || true !== ($result =
                    version_compare(SettingService::getSetting('application_generator_version'), $compatable, '>='))) {

                throw new LayoutException('This layout is not compatible with current CMS version');
            }

            if (!$version || !$vendor || !$vendorEmail) {
                throw new LayoutException('It is impossible to determine the layout version, vendor or vendor email');
            }

            // clear caches
            $this->clearLayoutCaches();

            $insert = $this->insert()
                ->into('layout_list')
                ->values([
                    'name' => $layoutName,
                    'type' => self::LAYOUT_TYPE_CUSTOM,
                    'version' => $version,
                    'vendor' => $vendor,
                    'vendor_email' => $vendorEmail
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the install custom layout event
        LayoutEvent::fireInstallCustomLayoutEvent($layoutName);

        return true;
    }

    /**
     * Upload custom layout
     *
     * @param array $formData
     *      string login required
     *      string password required
     *      array layout required
     * @param string $host
     * @return boolean|string
     */
    public function uploadCustomLayout(array $formData, $host)
    {
        $uploadResult = true;

        try {
            // create a tmp dir
            $tmpDirName = $this->generateTmpDir();
            ApplicationFileSystemUtility::createDir($tmpDirName);

            // unzip a custom layout into the tmp dir
            $this->unzipFiles($formData['layout']['tmp_name'], $tmpDirName);

            // check the layout's config
            if (!file_exists($tmpDirName . '/layout_config.php')) {
                throw new LayoutException('Cannot define the layout\'s config file');
            }

            // get the layout's config
            $layoutConfig = include $tmpDirName . '/layout_config.php';
            $layoutName = !empty($layoutConfig['layout_name']) ? mb_strtolower($layoutConfig['layout_name']) : null;

            // check the layout name
            if (!$layoutName) {
                throw new LayoutException('Cannot define the layout\'s name into the config file');
            }

            // upload layout's files
            $this->uploadLayoutFiles($layoutName, $layoutConfig, $tmpDirName, $host, $formData);
        }
        catch (Exception $e) {            
            ApplicationErrorLogger::log($e);
            $uploadResult = $e->getMessage();
        }

        // remove the tmp dir
        if (file_exists($tmpDirName)) {
            ApplicationFileSystemUtility::deleteFiles($tmpDirName, [], false, true);
        }

        // fire the upload custom layout event
        if (true === $uploadResult) {
            LayoutEvent::fireUploadCustomLayoutEvent($layoutName);
        }

        return $uploadResult;
    }

    /**
     * Delete custom layout
     *
     * @param string $layoutName
     * @param array $formData
     *      string login required
     *      string password required
     * @param string $host
     * @return boolean|string
     */
    public function deleteCustomLayout($layoutName, array $formData, $host)
    {
        try {
            // delete a layout dir
            $globalLayoutPath = basename(APPLICATION_PUBLIC) . '/' . ApplicationService::getLayoutPath(false) . '/' . $layoutName;
            $ftp = new ApplicationFtpUtility($host, $formData['login'], $formData['password']);
            $ftp->removeDirectory($globalLayoutPath);

            // delete modules templates
            $globalModulePath = ApplicationService::getModulePath(false);
            $localModulePath = APPLICATION_ROOT. '/' . $globalModulePath;
            $directoryIterator = new DirectoryIterator($localModulePath);

            foreach($directoryIterator as $module) {
                if ($module->isDot() || !$module->isDir()) {
                    continue;
                }

                $moduleTemplateDir = $localModulePath . '/' . 
                        $module->getFileName() . '/' . ApplicationService::getModuleViewDir() . '/' . $layoutName;

                if (file_exists($moduleTemplateDir)) {
                    $ftp->removeDirectory($globalModulePath .  '/' . 
                            $module->getFileName() . '/' . ApplicationService::getModuleViewDir() . '/' . $layoutName);
                }
            }
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        LayoutEvent::fireDeleteCustomLayoutEvent($layoutName);

        return true;
    }

    /**
     * Upload layout updates
     *
     * @param array $formData
     *      string login required
     *      string password required
     *      array layout required
     * @param string $host
     * @return array|string
     */
    public function uploadLayoutUpdates(array $formData, $host)
    {
        $uploadResult = true;

        try {
            // create a tmp dir
            $tmpDirName = $this->generateTmpDir();
            ApplicationFileSystemUtility::createDir($tmpDirName);

            // unzip a layout updates into the tmp dir
            $this->unzipFiles($formData['layout']['tmp_name'], $tmpDirName);

            // check the layout's config
            if (!file_exists($tmpDirName . '/update_layout_config.php')) {
                throw new LayoutException('Cannot define the layout\'s config file');
            }

            // get the layout's config
            $updateLayoutConfig = include $tmpDirName . '/update_layout_config.php';

            // get updates params
            $layoutCompatable = !empty($updateLayoutConfig['compatable']) ? $updateLayoutConfig['compatable'] : null;
            $layoutName = !empty($updateLayoutConfig['layout_name']) ? $updateLayoutConfig['layout_name'] : null;
            $layoutVersion = !empty($updateLayoutConfig['version']) ? $updateLayoutConfig['version'] : null;
            $layoutVendor = !empty($updateLayoutConfig['vendor']) ? $updateLayoutConfig['vendor'] : null;
            $layoutVendorEmail = !empty($updateLayoutConfig['vendor_email']) ? $updateLayoutConfig['vendor_email'] : null;

            // check the layout existing
            if (!$layoutName) {
                throw new LayoutException('Layout not found');
            }

            $layoutInstalled = true;

            // get layout info from db
            if (null == ($layoutInfo = $this->getLayoutInfo($layoutName))) {
                // get info from config
                if (false === ($layoutInfo = $this->getCustomLayoutInstallConfig($layoutName))) {
                    // nothing to update
                    throw new LayoutException('Layout not found');
                }

                $layoutInstalled = false;
            }

            // compare the layout options
            if (!$layoutVendor || !$layoutVendorEmail
                    || empty($layoutInfo['vendor']) || empty($layoutInfo['vendor_email'])
                    || strcasecmp($layoutVendor, $layoutInfo['vendor']) <> 0
                    || strcasecmp($layoutVendorEmail, $layoutInfo['vendor_email']) <> 0) {

                throw new LayoutException('Layout not found');
            }

            if (!$layoutCompatable || true !== ($result =
                    version_compare(SettingService::getSetting('application_generator_version'), $layoutCompatable, '>='))) {

                throw new LayoutException('These updates are not compatible with current CMS version');
            }

            // compare the layout versions
            if (!$layoutVersion
                    || empty($layoutInfo['version'])
                    || version_compare($layoutVersion, $layoutInfo['version']) <= 0) {

                throw new LayoutException('This layout updates are not necessary or not defined');
            }

            // clear caches
            $this->clearLayoutCaches();

            // upload layout's updates
            $this->uploadLayoutFiles($layoutName, $updateLayoutConfig, $tmpDirName, $host, $formData, false);

            // update version
            if ($layoutInstalled) {
                $update = $this->update()
                    ->table('layout_list')
                    ->set([
                        'version' => $layoutVersion
                    ])
                    ->where([
                        'name' => $layoutName
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }
        }
        catch (Exception $e) {            
            ApplicationErrorLogger::log($e);
            $uploadResult = $e->getMessage();
        }

        // remove the tmp dir
        if (file_exists($tmpDirName)) {
            ApplicationFileSystemUtility::deleteFiles($tmpDirName, [], false, true);
        }

        // fire the upload layout updates event
        if (true === $uploadResult) {
            LayoutEvent::fireUploadLayoutUpdatesEvent($layoutName);
        }

        // return an error description
        return $uploadResult;
    }

    /**
     * Upload layout files
     *
     * @param string $layoutName
     * @param array $layoutConfig
     * @param string $tmpDirName
     * @param string $host
     * @param array $formData
     *      string login required
     *      string password required
     *      array layout required
     * @param boolean $checkInstallConfig
     * @throws \Layout\Exception\LayoutException
     * return void
     */
    protected function uploadLayoutFiles($layoutName, array $layoutConfig, $tmpDirName, $host, array $formData, $checkInstallConfig = true)
    {
        $updated = false;

        // get the layout's path
        $layoutPath = !empty($layoutConfig['layout_path']) 
            ? $tmpDirName . '/' . $layoutConfig['layout_path'] 
            : null;

        // check the layout existing
        if ($layoutPath && (!file_exists($layoutPath) || !is_dir($layoutPath))) {
            throw new LayoutException('Cannot define the layout\'s path into the config file');
        }

        $globalLayoutPath = basename(APPLICATION_PUBLIC) . '/' . ApplicationService::getLayoutPath(false) . '/' . $layoutName;
        $localLayoutPath = dirname(APPLICATION_PUBLIC) . '/' . $globalLayoutPath;

        // check the layout install config
        if ($checkInstallConfig) {
            if (!file_exists($layoutPath . '/' . $this->layoutInstallConfig)) {
                throw new LayoutException('Layout not found');
            }

            if (file_exists($localLayoutPath)) {
                throw new LayoutException('Layout already uploaded');
            }
        }

        $ftp = new ApplicationFtpUtility($host, $formData['login'], $formData['password']);

        if ($layoutPath) {
            // upload the layout via FTP 
            $ftp->createDirectory($globalLayoutPath, true);
            $ftp->copyDirectory($layoutPath, $globalLayoutPath);
            $updated = true;
        }

        // check modules templates 
        if (!empty($layoutConfig['module_path']) && is_array($layoutConfig['module_path'])) {
            $globalModulePath = ApplicationService::getModulePath(false);
            $localModulePath = APPLICATION_ROOT. '/' . $globalModulePath;

            // upload modules templates
            foreach ($layoutConfig['module_path'] as $moduleName => $template) {
                // skip non existing modules
                if (!file_exists($localModulePath . '/' . $moduleName)) {
                    continue;
                }

                $templateDir = $tmpDirName . '/' . $template;

                // check the template existing
                if (!file_exists($templateDir) || !is_dir($templateDir)) {
                    throw new LayoutException('Cannot define the template\'s path into the config file');
                }

                $moduleTemplateDir = $globalModulePath .  '/' . 
                        $moduleName . '/' . ApplicationService::getModuleViewDir() . '/' . $layoutName;

                $ftp->createDirectory($moduleTemplateDir, true);
                $ftp->copyDirectory($templateDir, $moduleTemplateDir);
                $updated = true;
            }
        }

        if (!$updated) {
            throw new LayoutException('Nothing to update the layout');
        }
    }

    /**
     * Get installed layouts
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string name
     *      string type
     * @return \Zend\Paginator\Paginator
     */
    public function getInstalledLayouts($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'name',
            'type',
            'version',
            'vendor',
            'email'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('layout_list')
            ->columns([
                'id',
                'name',
                'type',
                'version',
                'vendor',
                'email' => 'vendor_email'
            ])
            ->order($orderBy . ' ' . $orderType);

        // filter by name
        if (!empty($filters['name'])) {
            $select->where([
                new LikePredicate('name', '%' . $filters['name'] . '%')
            ]);
        }

        // filter by type
        if (!empty($filters['type'])) {
            switch ($filters['type']) {
                case self::LAYOUT_TYPE_CUSTOM :
                case self::LAYOUT_TYPE_SYSTEM :
                    $select->where([
                        'type' => $filters['type']
                    ]);
                    break;

                default :
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Uninnstall custom layout
     *
     * @param array $layout
     *      integer id
     *      string name
     *      string type 
     *      string version 
     *      string vendor 
     *      string vendor_email 
     * @return boolean|string
     */
    public function uninstallCustomLayout(array $layout)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // clear caches
            $this->clearLayoutCaches();

            $query = $this->delete('layout_list')
                ->where([
                    'id' => $layout['id']
                ]);

            $statement = $this->prepareStatementForSqlObject($query);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the uninstall custom layout event
        LayoutEvent::fireUninstallCustomLayoutEvent($layout['name']);

        return true;
    }
}
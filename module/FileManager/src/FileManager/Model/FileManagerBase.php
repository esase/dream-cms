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
namespace FileManager\Model;

use Application\Model\ApplicationAbstractBase;
use Application\Service\ApplicationSetting as SettingService;
use Application\Service\Application as ApplicationService;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Utility\ApplicationErrorLogger;
use Application\Utility\ApplicationSlug as SlugUtility;
use FileManager\Event\FileManagerEvent;
use User\Service\UserIdentity as UserIdentityService;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Paginator\Paginator;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;
use CallbackFilterIterator;

class FileManagerBase extends ApplicationAbstractBase
{
    /**
     * Files directory
     *
     * @var string
     */
    protected static $filesDir = 'filemanager/';

    /**
     * Home directory name
     *
     * @var string
     */
    protected static $homeDirectoryName = 'home';

    /**
     * Directory name pattern
     *
     * @var string
     */
    protected static $directoryNamePattern = '0-9a-z_';

    /**
     * File name pattern
     *
     * @var string
     */
    protected static $fileNamePattern = '0-9a-z\.\_\-\(\)';

    /**
     * Get directory name pattern
     *
     * @return string
     */
    public static function getDirectoryNamePattern()
    {
        return self::$directoryNamePattern;
    }

    /**
     * Get file name pattern
     *
     * @return string
     */
    public static function getFileNamePattern()
    {
        return self::$fileNamePattern;
    }

    /**
     * Get user's files dir
     *
     * @param integer $userId
     * @return string
     */
    public static function getUserFilesDir($userId = null)
    {
        return self::$filesDir . (!$userId ? UserIdentityService::getCurrentUserIdentity()['user_id'] : $userId);
    }

    /**
     * Get user's base files dir
     *
     * @param integer $userId
     * @return string
     */
    public static function getUserBaseFilesDir($userId = null)
    {
        return ApplicationService::getResourcesDir() . self::getUserFilesDir($userId);
    }

    /**
     * Get user's base files url
     *
     * @return string
     */
    public static function getUserBaseFilesUrl()
    {
        return ApplicationService::getResourcesUrl() . self::getUserFilesDir();
    }

    /**
     * Get home directory name
     *
     * @return sting
     */
    public static function getHomeDirectoryName()
    {
        return self::$homeDirectoryName;
    }

    /**
     * Process directory path
     *
     * @param sting $path
     * @return string
     */
    public static function processDirectoryPath($path)
    {
        $path = preg_replace('/[^' . self::$directoryNamePattern . '\/]/', null, $path);
        $path = explode('/', $path);

        // remove empty parts
        $processedPath = null;
        foreach ($path as $point) {
            if (!$point) {
                continue;
            }

            $processedPath .= $point . '/';
        }

        return rtrim($processedPath, '/');
    }

    /**
     * Delete the user's home directory
     *
     * @param integer $userId
     * @return boolean|string
     */
    public function deleteUserHomeDirectory($userId)
    {
        $result = true;
        $directoryPath = self::getUserBaseFilesDir($userId);
        $directoryPath = file_exists($directoryPath) ? $directoryPath : null;
        
        if (file_exists($directoryPath)) {
            $result =  FileSystemUtility::deleteFiles($directoryPath, [], false, true);
        }

        if (true === $result) {
            if ($result) {
                // fire the delete directory event
                FileManagerEvent::fireDeleteDirectoryEvent($directoryPath, true);
            }

            return $directoryPath;
        }

        ApplicationErrorLogger::log('Cannot delete files and directories for user id: ' . $userId);

        return false;
    }

    /**
     * Get the user's directory
     *
     * @param string $path
     * @param boolean $basePath
     * @return boolean|string
     */
    public function getUserDirectory($path, $basePath = true)
    {
        // process the directory path
        if (null == ($path = self::processDirectoryPath($path))) {
            return false;
        }

        $userDir = self::getUserBaseFilesDir() . '/' . $path;

        if (file_exists($userDir)) {
            return $basePath
                ? $userDir . '/'
                : self::getUserFilesDir() . '/' . $path . '/';
        }

        return false;
    }

    /**
     * Add user's file
     *
     * @param array $fileInfo
     *      string name
     *      string type
     *      string tmp_name
     *      integer error
     *      integer size
     * @param string $path
     * @return boolean|string
     */
    public function addUserFile(array $fileInfo, $path)
    {
        if (false !== ($userDirectory = $this->getUserDirectory($path, false))) {
            if (false !== ($fileName = FileSystemUtility::uploadResourceFile(self::slugifyFileName($fileInfo['name']),
                    $fileInfo, $userDirectory, false))) {
        
                // fire the add file event
                FileManagerEvent::fireAddFileEvent($this->getUserDirectory($path) . $fileName);

                return $fileName;
            }

            return false;
        }

        return false;
    }

    /**
     * Slugify a file name
     *
     * @param string $fileName
     * @param boolean $addSalt
     * @param boolean $processFullName
     * @return string
     */
    public static function slugifyFileName($fileName, $addSalt = true, $processFullName = false)
    {
        $fileExtension = FileSystemUtility::getFileExtension($fileName);
        $fileName = FileSystemUtility::getFileName($fileName);

        $maxFileNameLength = !$processFullName && $fileExtension
            ? (int) SettingService::getSetting('file_manager_file_name_length') - (strlen($fileExtension) + 1)
            : (int) SettingService::getSetting('file_manager_file_name_length');

        $slug = SlugUtility::slugify(($processFullName && $fileExtension
                ? $fileName . '.' . $fileExtension : $fileName), $maxFileNameLength, '_', 0, self::$fileNamePattern);

        if (!$slug && $addSalt) {
            $slug = SlugUtility::generateRandomSlug($maxFileNameLength);
        }

        return $slug;
    }

    /**
     * Add user's directory
     *
     * @param string $name
     * @param string $path
     * @return boolean
     */
    public function addUserDirectory($name, $path)
    {
        if (false !== ($userDirectory = $this->getUserDirectory($path))) {
            try {
                FileSystemUtility::createDir($userDirectory . $name);
            }
            catch (Exception $e) {
                ApplicationErrorLogger::log($e);

                return false;
            }

            // fire the add directory event
            FileManagerEvent::fireAddDirectoryEvent($userDirectory . $name);

            return true;
        }

        return false;
    }

    /**
     * Edit a file
     *
     * @param string $fileName
     * @param string $oldFullPath
     * @param string $newFullPath
     * @param boolean $isDirectory
     * @return boolean
     */
    public function editFile($fileName, $oldFullPath, $newFullPath, $isDirectory)
    {
        if (!$isDirectory) {
            $fileName .= '.' . FileSystemUtility::getFileExtension($oldFullPath);
        }

        try {
            if (true !== ($result = rename($oldFullPath, $newFullPath . $fileName))) {
                return false;
            }
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);

            return false;
        }

        // fire the event
        $isDirectory
            ? FileManagerEvent::fireEditDirectoryEvent($oldFullPath, $newFullPath . $fileName)
            : FileManagerEvent::fireEditFileEvent($oldFullPath, $newFullPath . $fileName);

        return $fileName;
    }

    /**
     * Delete user's file or directory
     *
     * @param string $fileName
     * @param string $path
     * @return boolean
     */ 
    public function deleteUserFile($fileName, $path)
    {
        // process the directory path
        if (null == ($path = self::processDirectoryPath($path))) {
            return false;
        }

        $fullPath = self::getUserBaseFilesDir() . '/' . $path . '/' . $fileName;
        $isDirectory = is_dir($fullPath);
        $result = FileSystemUtility::deleteFiles($fullPath, [], false, true);

        // fire the event
        if ($result) {
            $isDirectory
                ? FileManagerEvent::fireDeleteDirectoryEvent($fullPath)
                : FileManagerEvent::fireDeleteFileEvent($fullPath);
        }

        return $result;
    }

    /**
     * Get files
     *
     * @param string $directory
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string file_type (image , file, media)
     *      string name
     *      string type (directory, file)
     * @return array
     */
    public function getFiles($directory, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'name',
            'size',
            'type',
            'date'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? SORT_DESC
            : SORT_ASC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'name';

        // get all directories and files
        $directoryIterator = new DirectoryIterator($directory);
        $files = new CallbackFilterIterator($directoryIterator, function($current) use ($filters) {
            if ($current->isDot() || $current->isLink()) {
                return false;
            }

            // filter by files type - image, media, etc
            if (!empty($filters['file_type']) && !$current->isDir()) {
                switch($filters['file_type']) {
                    // show only images
                    case 'image' :
                        if (!in_array(FileSystemUtility::getFileExtension($current->getFileName()),
                                explode(',', strtolower(SettingService::getSetting('file_manager_image_extensions'))))) {

                            return false;
                        }
                        break;
                    case 'media' :
                        if (!in_array(FileSystemUtility::getFileExtension($current->getFileName()),
                                explode(',', strtolower(SettingService::getSetting('file_manager_media_extensions'))))) {

                            return false;
                        }
                        break;
                    default :
                }
            }

            // filter by type
            if (!empty($filters['type'])) {
                switch($filters['type']) {
                    case 'directory' :
                        if ($current->isFile()) {
                            return false;
                        }
                        break;

                    case 'file' :
                        if ($current->isDir()) {
                            return false;
                        }
                        break;

                    default :
                }
            }

            // filter by name
            return (empty($filters['name']) || null != stristr($current->getFileName(), $filters['name']));
        });

        $processedFiles = [];
        $orderValues    = [];

        foreach($files as $data) {
            $fileInfo = [
                'name' => $data->getFileName(),
                'type' => $data->isDir(),
                'date' => $data->getMTime(),
                'size' => !$data->isDir() ? $data->getSize() : 0
            ];

            $processedFiles[] = $fileInfo;
            $orderValues[]    = $fileInfo[$orderBy];
        }

        array_multisort($orderValues, $orderType, $processedFiles);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedFiles));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get the user's list of directories
     *
     * @return array|boolean
     */
    public function getUserDirectories()
    {
        $baseDir = self::getUserBaseFilesDir();
        $homeDir = $baseDir . '/'. self::$homeDirectoryName;

        // check the home directory existing
        if (!file_exists($homeDir)) {
            // create a new home directory            
            try {
                FileSystemUtility::createDir($homeDir);
            }
            catch (Exception $e) {
                ApplicationErrorLogger::log($e);

                return false;
            }
        }

        $iterator = new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        $directories = [];

        // get list of user's directories
        foreach ($files as $file) {
            if (!$file->isDir()) {
                continue;
            }

            // PHP_EOL - fix for array_merge_recursive (values should be as strings)
            $path = [$file->getFilename() . PHP_EOL  => []];
            for ($depth = $files->getDepth() - 1; $depth >= 0; $depth--) {
                $path = [$files->getSubIterator($depth)->current()->getFilename() . PHP_EOL => $path];
            }

            $directories = array_merge_recursive($directories, $path);
        }

        return $directories;
    }
}
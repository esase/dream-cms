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
namespace Application\Utility;

use Application\Service\Application as ApplicationService;
use Application\Exception\ApplicationException;
use Application\Utility\ApplicationSlug as SlugUtility;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ApplicationFileSystem
{
    /**
     * Default folder permissions
     */
    const DEFAULT_FOLDER_PERMISSIONS = 0777;

    /**
     * Default file permissions
     */
    const DEFAULT_FILE_PERMISSIONS = 0666;


    /**
     * System files
     *
     * @var array
     */
    protected static $systemFiles = [
        '.htaccess'
    ];

    /**
     * Convert bytes
     *
     * @param integer $bytes
     * @return string
     */
    public static function convertBytes($bytes)
    {
        if ((int) $bytes) {
            $unit = intval(log($bytes, 1024));
            $units = ['B', 'KB', 'MB', 'GB'];

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes ? $bytes : null;
    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param integer $permission
     * @throws \Application\Exception\ApplicationException
     * @return void
     */
    public static function createDir($path, $permission = self::DEFAULT_FOLDER_PERMISSIONS)
    {
        if (true !== ($result = mkdir($path, $permission, true))) {
            throw new ApplicationException ('Failed to create directory - ' . $path);
        }

        @chmod($path, self::DEFAULT_FOLDER_PERMISSIONS);
    }

    /**
     * Check a directory
     *
     * @param string $path
     * @return boolean
     */
    public static function isDirectoryEmpty($path)
    {
        return (($files = scandir($path)) && count($files) <= 2) ? true : false;
    }

    /**
     * Delete a resource file
     *
     * @param string $file
     * @param string $path
     * @return boolean
     */
    public static function deleteResourceFile($file, $path)
    {
        $filePath = ApplicationService::getResourcesDir() . $path . $file;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Get the file's extension
     *
     * @param string $fileName
     * @param boolean $toLower
     * @return string
     */
    public static function getFileExtension($fileName, $toLower = true)
    {
        $extension = self::getFileInfo($fileName)->getExtension();

        return $toLower
            ? strtolower($extension)
            : $extension;
    }

    /**
     * Get file info
     *
     * @param string $fileName
     * @return object
     */
    protected static function getFileInfo($fileName)
    {
       return new SplFileInfo($fileName);
    }

    /**
     * Get the file's  name
     * 
     * @param string $fileName
     * @param boolean $removeExtension
     * @return string
     */
    public static function getFileName($fileName, $removeExtension = true)
    {
        $file = self::getFileInfo($fileName)->getFileName();

        if ($removeExtension && null != ($extension = self::getFileExtension($file))) {
            $file = preg_replace('/.' . preg_quote($extension) . '$/i', '', $file);
        }

        return $file;
    }

    /**
     * Upload a resource file
     *
     * @param integer|string $objectId
     * @param array $file
     *      string name
     *      string type
     *      string tmp_name
     *      integer error
     *      integer size
     * @param string $path
     * @param boolean $addSalt
     * @param integer $saltLength
     * @return string|boolean
     */
    public static function uploadResourceFile($objectId, $file, $path, $addSalt = true, $saltLength = 5)
    {
        $fileInfo = pathinfo($file['name']);
        $salt = $addSalt
            ? '_' . SlugUtility::generateRandomSlug($saltLength)
            : null;

        $fileName = $objectId . $salt .
                (!empty($fileInfo['extension']) ? '.' . strtolower($fileInfo['extension']) : null);

        $basePath = ApplicationService::getResourcesDir() . $path;

        if (is_writable($basePath)) {
            if (true === ($result = move_uploaded_file($file['tmp_name'], $basePath . $fileName))) {
                @chmod($basePath . $fileName, self::DEFAULT_FILE_PERMISSIONS);

                return $fileName;
            }
        }

        return false;
    }

    /**
     * Delete files and folders (recursively)
     *
     * @param string $path
     * @param array $notDeletable
     * @param boolean $useNotDeletableFiles
     * @param boolean $removeCurrentDirectory
     * @return boolean
     */
    public static function deleteFiles($path, array $notDeletable = [], $useNotDeletableFiles = true, $removeCurrentDirectory = false)
    {
        // check a path
        if (!file_exists($path)) {
            return false;
        }

        // delete a file
        if (is_file($path)) {
            return unlink($path);
        }

        // check for the not deletable files
        $notDeletable = $useNotDeletableFiles
            ? (!$notDeletable ? self::$systemFiles : $notDeletable)
            : [];

        // open and read all child directories and files
        $iterator = new RecursiveDirectoryIterator($path);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        // delete child files and directories
        foreach($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..' || in_array($file->
                    getFilename(), $notDeletable) || ($file->isDir() && !self::isDirectoryEmpty($file->getRealPath()))) {

                continue;
            }

            if (false === ($result = $file->
                    isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath()))) {

                return $result;
            }
        }

        // remove current directory
        return $removeCurrentDirectory ? rmdir($path) : true;
    }
}
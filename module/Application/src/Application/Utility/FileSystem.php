<?php

namespace Application\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Application\Service\Service as ApplicationService;
use Exception;

class FileSystem
{
    /**
     * Default files permission
     */
    const DEFAULT_PERMISSION = 0777;

    /**
     * System files
     * @var array
     */
    protected static $systemFiles = array(
        '.htaccess'
    );

    /**
     * Create a directory
     *
     * @param string $path
     * @param integer $permission
     * @return void
     */
    public static function createDir($path, $permission = self::DEFAULT_PERMISSION)
    {
        if (true !== ($result = mkdir($path, $permission, true))) {
            throw new Exception ('Failed to create directory - ' . $path);
        }
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
     * @return string
     */
    public static function getFileExtension($fileName)
    {
        return strtolower(end(explode('.', $fileName)));
    }

    /**
     * Upload a resource file
     *
     * @param integer $objectId
     * @param array $file
     *      string name
     *      string type
     *      string tmp_name
     *      integer error
     *      integer size
     * @param string $path
     * @return string|boolean
     */
    public static function uploadResourceFile($objectId, $file, $path)
    {
        //TODO: add a salt to name
        $fileInfo = pathinfo($file['name']);
        $fileName = $objectId . (!empty($fileInfo['extension']) ? '.' . $fileInfo['extension'] : null);
        $basePath = ApplicationService::getResourcesDir() . $path;

        if (is_writable($basePath)) {
            if (true === ($result = move_uploaded_file($file['tmp_name'], $basePath . $fileName))) {
                return $fileName;
            }
        }

        return false;
    }

    /**
     * Delete files and folders
     *
     * @param string $path
     * @param array $undeletable
     * @return boolean
     */
    public static function deleteFiles($path, array $undeletable = array())
    {
        if (!$undeletable) {
            $undeletable = self::$systemFiles;
        }

        $iterator = new RecursiveDirectoryIterator($path);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        // delete files and directories
        foreach($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..' || in_array($file->
                    getFilename(), $undeletable) || ($file->isDir() && !self::isDirectoryEmpty($file->getRealPath()))) {

                continue;
            }

            if (false === ($result = $file->
                    isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath()))) {

                return $result;
            }
        }

        return true;
    }
}
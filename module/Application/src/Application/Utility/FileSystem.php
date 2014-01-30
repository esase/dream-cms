<?php

namespace Application\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Application\Service\Service as ApplicationService;

class FileSystem
{
    /**
     * System files
     * @var array
     */
    protected static $systemFiles = array(
        '.htaccess'
    );

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
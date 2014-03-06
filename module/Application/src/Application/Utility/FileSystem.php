<?php

namespace Application\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Application\Service\Service as ApplicationService;
use Exception;
use Zend\Math\Rand;

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
     * @param boolean $addSalt
     * @param integer $saltLength
     * @param string $saltChars
     * @paam string $saltChars
     * @return string|boolean
     */
    public static function uploadResourceFile($objectId, $file, $path, $addSalt = true, $saltLength = 5, $saltChars = 'abcdefghijklmnopqrstuvwxyz')
    {
        $fileInfo = pathinfo($file['name']);
        $salt = $addSalt
            ? '_' . Rand::getString($saltLength, $saltChars, true)
            : null;

        $fileName = $objectId . $salt . (!empty($fileInfo['extension']) ? '.' . $fileInfo['extension'] : null);
        $basePath = ApplicationService::getResourcesDir() . $path;

        if (is_writable($basePath)) {
            if (true === ($result = move_uploaded_file($file['tmp_name'], $basePath . $fileName))) {
                return $fileName;
            }
        }

        return false;
    }

    /**
     * Delete files and folders (recursively)
     *
     * @param string $path
     * @param array $undeletable
     * @param boolean $useUndeletableFiles
     * @param boolean $removeCurrentDirectory
     * @return boolean
     */
    public static function deleteFiles($path, array $undeletable = array(), $useUndeletableFiles = true, $removeCurrentDirectory = false)
    {
        // check a path
        if (!file_exists($path)) {
            return false;
        }

        // delete a file
        if (is_file($path)) {
            return unlink($path);
        }

        // check for the undeletable files
        $undeletable = $useUndeletableFiles
            ? (!$undeletable ? self::$systemFiles : $undeletable)
            : array();

        // open and read all child directories and files
        $iterator = new RecursiveDirectoryIterator($path);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        // delete child files and directories
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

        // remove current directory
        return $removeCurrentDirectory
            ? rmdir($path)
            : true;
    }
}
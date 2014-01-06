<?php

namespace Application\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
     * Check directory
     *
     * @param string $path
     * @return boolean
     */
    public static function isDirectoryEmpty($path)
    {
        return (($files = scandir($path)) && count($files) <= 2) ? true : false;
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
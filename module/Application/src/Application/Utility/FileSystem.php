<?php

namespace Application\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystem
{
    /**
     * Delete files and folders
     *
     * @param string $path
     * @param array $undeletable
     * @return boolean
     */
    public static function deleteFiles($path, array $undeletable = array())
    {
        $iterator = new RecursiveDirectoryIterator($path);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        // delete files and directories
        foreach($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..' ||
                        in_array($file->getFilename(), $undeletable)) {

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
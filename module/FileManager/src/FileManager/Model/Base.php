<?php

namespace FileManager\Model;

use Application\Model\AbstractBase;
use Application\Service\Service as ApplicationService;
use Application\Utility\FileSystem as FileSystemUtility;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use DirectoryIterator;
use CallbackFilterIterator;
use Zend\Paginator\Paginator;
use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Application\Utility\ErrorLogger;

class Base extends AbstractBase
{
    /**
     * Files directory
     * @var string
     */
    protected static $filesDir = 'file_manager/';

    /**
     * Home directory name
     * @var string
     */
    protected static $homeDirectoryName = 'home';

    /**
     * Directory name pattern
     * @var string
     */
    protected static $directoryNamePattern = '0-9a-z';

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
     * Get user's base files dir
     *
     * @return string
     */
    public static function getUserBaseFilesDir()
    {
        return ApplicationService::getResourcesDir() .
                self::$filesDir . ApplicationService::getCurrentUserIdentity()->user_id;
    }

    /**
     * Get user's base files url
     *
     * @return string
     */
    public static function getUserBaseFilesUrl()
    {
        return ApplicationService::getResourcesUrl() .
                self::$filesDir . ApplicationService::getCurrentUserIdentity()->user_id;
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
     * Get the user's directory
     *
     * @param string $path
     * @return boolean|string
     */
    public function getUserDirectory($path)
    {
        // process the directory path
        if (null == ($path = self::processDirectoryPath($path))) {
            return false;
        }

        $userDir = self::getUserBaseFilesDir() . '/' . $path;

        if (file_exists($userDir)) {
            return $userDir;
        }

        return false;
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
                FileSystemUtility::createDir($userDirectory . '/' . $name);
            }
            catch (Exception $e) {
                ErrorLogger::log($e);
                return false;
            }

            return true;
        }

        return false;
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

        return FileSystemUtility::deleteFiles(self::
                getUserBaseFilesDir() . '/' . $path . '/' . $fileName, array(), false, true);
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
    public function getFiles($directory, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'name',
            'size',
            'type',
            'date'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? SORT_DESC
            : SORT_ASC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'name';

        // get all directories and files
        $directoryIterator = new DirectoryIterator($directory);
        $files = new CallbackFilterIterator($directoryIterator, function($current, $key, $iterator) use ($filters) {
            if ($current->isDot() || $current->isLink()) {
                return false;
            }

            // filter by files type - image, media, etc
            if (!empty($filters['file_type']) && !$current->isDir()) {
                switch($filters['file_type']) {
                    // show only images
                    case 'image' :
                        if (!in_array(FileSystemUtility::getFileExtension($current->getFileName()),
                                explode(',', ApplicationService::getSetting('file_manager_image_extensions')))) {

                            return false;
                        }
                        break;
                    case 'media' :
                        if (!in_array(FileSystemUtility::getFileExtension($current->getFileName()),
                                explode(',', ApplicationService::getSetting('file_manager_media_extensions')))) {

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

        $processedFiles = array();
        $orderValues    = array();

        foreach($files as $data) {
            $fileInfo = array(
                'name' => $data->getFileName(),
                'type' => $data->isDir(),
                'date' => $data->getMTime(),
                'size' => !$data->isDir() ? $data->getSize() : 0
            );

            $processedFiles[] = $fileInfo;
            $orderValues[]    = $fileInfo[$orderBy];
        }

        array_multisort($orderValues, $orderType, $processedFiles);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedFiles));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

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
                ErrorLogger::log($e);
                return false;
            }
        }

        $iterator = new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        $directories = array();

        // get list of user's directories
        foreach ($files as $file) {
            if (!$file->isDir()) {
                continue;
            }

            // PHP_EOL - fix for array_merge_recursive (values should be as strings)
            $path = array($file->getFilename() . PHP_EOL  => array());
            for ($depth = $files->getDepth() - 1; $depth >= 0; $depth--) {
                $path = array($files->getSubIterator($depth)->current()->getFilename() . PHP_EOL => $path);
            }

            $directories = array_merge_recursive($directories, $path);
        }

        return $directories;
    }
}
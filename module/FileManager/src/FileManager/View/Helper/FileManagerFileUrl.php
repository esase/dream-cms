<?php
namespace FileManager\View\Helper;

use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Zend\View\Helper\AbstractHelper;

class FileManagerFileUrl extends AbstractHelper
{
    /**
     * Generate a file url
     *
     * @param string $fileName
     * @param array $options
     *      string path
     *      array filters
     * @return string
     */
    public function __invoke($fileName, array $options)
    {
        $currentPath  = $options['path'] . '/' . $fileName;

        // generate a directory navigation link
        if (is_dir(FileManagerBaseModel::
                getUserBaseFilesDir() . '/'. $options['path'] . '/' . $fileName)) {

            // get the directory url
            $directoryUrl = $this->getView()->url('application/page', [
                'controller' => $this->getView()->applicationRoute()->getParam('controller'),
                'action' => $this->getView()->applicationRoute()->getParam('action')
            ], ['force_canonical' => true, 'query' => ['path' => $currentPath] + $options['filters']]);

            return $this->getView()->partial('file-manager/patrial/directory-url', [
                'name' => $fileName,
                'url' => $directoryUrl
            ]);
        }

        // generate a file link
        return $this->getView()->partial('file-manager/patrial/file-url', [
            'file_extension' => FileSystemUtility::getFileExtension($fileName),
            'name' => $fileName,
            'url' => FileManagerBaseModel::getUserBaseFilesUrl() . '/' . $currentPath
        ]);
    }
}
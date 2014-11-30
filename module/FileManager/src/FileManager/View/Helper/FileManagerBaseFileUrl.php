<?php
namespace FileManager\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;

class FileManagerBaseFileUrl extends AbstractHelper
{
    /**
     * Generate a base file url
     *
     * @param string $currentPath
     * @return string
     */
    public function __invoke($currentPath = null)
    {
        return $this->getView()->
                serverUrl(FileManagerBaseModel::getUserBaseFilesUrl() . '/' . ($currentPath ? $currentPath . '/' : null));
    }
}
<?php
namespace FileManager\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;

class FileManagerDirectoryTree extends AbstractHelper
{
    /**
     * Generate a tree
     *
     * @param array|boolean $userDirectories
     * @param string $treeId
     * @param string $currentPath
     * @param array $filters
     * @param string $treeClass
     * @return string
     */
    public function __invoke($userDirectories = [], $treeId, $currentPath, array $filters = [], $treeClass = 'filetree')
    {
        $currentPath = $currentPath
            ? FileManagerBaseModel::processDirectoryPath($currentPath)
            : FileManagerBaseModel::getHomeDirectoryName();

        return $this->getView()->partial('file-manager/patrial/directory-tree', [
            'id' => $treeId,
            'class' => $treeClass,
            'items' => $this->processDirectories($userDirectories, $currentPath, $filters)
        ]);
    }

    /**
     * Process directories
     *
     * @param array $userDirectories
     * @param string $currentPath
     * @paam array $filters
     * @param string $parentDirectory
     * @return sting
     */
    protected function processDirectories($userDirectories, $currentPath, $filters, $parentDirectory = null)
    {
        $content = null;

        foreach ($userDirectories as $directoryName => $subDirectories) {
            $directoryName = str_replace(PHP_EOL, null, $directoryName);

            // get a directory's url
            $path = $parentDirectory ? $parentDirectory . '/' . $directoryName : $directoryName;
            $urlParams = ['path' => $path] + $filters;
            $url = null;

            if ($currentPath != $path) {
                $url = $this->getView()->url('application/page', [
                    'controller' => $this->getView()->applicationRoute()->getParam('controller'),
                    'action' => $this->getView()->applicationRoute()->getParam('action')
                ], ['force_canonical' => true, 'query' => $urlParams]);
            }

            $content .= $this->getView()->partial('file-manager/patrial/directory-tree-item-start', [
                'url' => $url,
                'name' => $directoryName
            ]);

            // process subdirectories
            if ($subDirectories) {
                $content .= $this->getView()->partial('file-manager/patrial/directory-tree-item-children', [
                    'children' => $this->processDirectories($subDirectories, $currentPath, $filters, $path)
                ]);
            }

            $content .= $this->getView()->partial('file-manager/patrial/directory-tree-item-end');
        }

        return $content;
    }
}
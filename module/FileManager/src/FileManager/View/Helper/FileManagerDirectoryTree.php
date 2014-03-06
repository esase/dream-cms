<?php

namespace FileManager\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use FileManager\Model\Base as FileManagerBaseModel;

class FileManagerDirectoryTree extends AbstractHelper
{
    /**
     * Generate a tree
     *
     * @param array $userDirectories
     * @param string $treeId
     * @param string $currentPath
     * @param array $filters
     * @param string $treeClass
     * @return string
     */
    public function __invoke(array $userDirectories = array(), $treeId, $currentPath, array $filters = array(), $treeClass = 'filetree')
    {
        $currentPath = $currentPath
            ? FileManagerBaseModel::processDirectoryPath($currentPath)
            : FileManagerBaseModel::getHomeDirectoryName();

        return  $userDirectories
            ? '<ul id="' . $treeId . '" class="' . $treeClass . '">' .
                    $this->processDirectories($userDirectories, $currentPath, $filters) . '</ul>'
            : null;
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

            // get directory's url
            $path = $parentDirectory ? $parentDirectory . '/' . $directoryName : $directoryName;
            $urlParams = $this->getView()->urlParamEncode(array(
                'path' => $path,
            ) + $filters);

            $url = $this->getView()->url('application', array(
                'controller' => $this->getView()->currentRoute()->getController(),
                'action' => $this->getView()->currentRoute()->getAction()), array('query' => $urlParams));

            $content .= $currentPath == $path
                ? '<li><span class="folder">' . $directoryName . '</span>'
                : '<li><span class="folder"><a href="' . $url . '">' . $directoryName . '</a></span>';

            // process subdirectories
            if ($subDirectories) {
                $content .= '<ul>' . $this->processDirectories($subDirectories, $currentPath, $filters, $path) . '</ul>';
            }

            $content .= '</li>';
        }

        return $content;
    }
}
<?php

namespace FileManager\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use FileManager\Model\Base as FileManagerBaseModel;
use Application\Utility\FileSystem as FileSystemUtility;

class FileUrl extends AbstractHelper
{
    /**
     * Default a folder image
     */
    const DEFAULT_FOLDER_IMAGE = 'folder.png';

    /**
     * Default a file image
     */
    const DEFAULT_FILE_IMAGE = 'file.png';

    /**
     * Default images extension
     */
    const DEFAULT_IMAGES_EXTENSION = '.png';

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
        $absolutePath = FileManagerBaseModel::getUserBaseFilesDir();
        $currentPath  = $options['path'] . '/' . $fileName;

        // generate a directory navigation link
        if (is_dir($absolutePath . '/'. $options['path'] . '/' . $fileName)) {
            $urlParams = array('path' => $currentPath) + $options['filters'];

            // get the directory url
            $directoryUrl = $this->getView()->url('application', array(
                'controller' => $this->getView()->currentRoute()->getController(),
                'action' => $this->getView()->currentRoute()->getAction()), array('query' => $urlParams));

            // get the directory's image url
            $imageUrl = $this->getView()->asset(self::DEFAULT_FOLDER_IMAGE, 'image/icon', 'file_manager');

            return '<img src="' . $imageUrl
                    . '" alt="directory" /> <a class="directory" href="' . $directoryUrl  . '">' . $fileName . '</a>';
        }
        else {
            // generate a file link
            $fileUrl =  $this->getView()->
                    serverUrl(FileManagerBaseModel::getUserBaseFilesUrl() . '/' . $currentPath);

            // get the file's image url
            if (false === ($imageUrl = $this->getView()->
                    asset(FileSystemUtility::getFileExtension($fileName) . self::DEFAULT_IMAGES_EXTENSION, 'image/icon', 'file_manager'))) {

                // get default the default image
                $imageUrl = $this->getView()->asset(self::DEFAULT_FILE_IMAGE, 'image/icon', 'file_manager');
            }

            return '<img src="' . $imageUrl
                    . '" alt="file" /> <a class="file" target="_blank" href="' . $fileUrl . '">' . $fileName . '</a>';
        }
    }
}
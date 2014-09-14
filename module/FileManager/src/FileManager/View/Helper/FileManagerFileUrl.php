<?php
namespace FileManager\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;

class FileManagerFileUrl extends AbstractHelper
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

        //TODO: make a refactoring here, code is ugly
        
        // generate a directory navigation link
        if (is_dir($absolutePath . '/'. $options['path'] . '/' . $fileName)) {
            $urlParams = array('path' => $currentPath) + $options['filters'];

            // get the directory url
            $directoryUrl = $this->getView()->url('administration', array(
                'controller' => $this->getView()->currentRoute()->getController(),
                'action' => $this->getView()->currentRoute()->getAction()), array('query' => $urlParams));

            // get the directory's image url
            $imageUrl = $this->getView()->layoutAsset(self::DEFAULT_FOLDER_IMAGE, 'image/icon', 'filemanager');

            return '<img src="' . $imageUrl
                    . '" alt="directory" /> <a class="directory" href="' . $directoryUrl  . '">' . $fileName . '</a>';
        }
        else {
            // generate a file link
            $fileUrl = FileManagerBaseModel::getUserBaseFilesUrl() . '/' . $currentPath;

            // get the file's image url
            if (false === ($imageUrl = $this->getView()->
                    layoutAsset(FileSystemUtility::getFileExtension($fileName) . self::DEFAULT_IMAGES_EXTENSION, 'image/icon', 'filemanager'))) {

                // get default the default image
                $imageUrl = $this->getView()->layoutAsset(self::DEFAULT_FILE_IMAGE, 'image/icon', 'filemanager');
            }

            return '<img src="' . $imageUrl
                    . '" alt="file" /> <a class="file" target="_blank" href="' . $fileUrl . '">' . $fileName . '</a>';
        }
    }
}
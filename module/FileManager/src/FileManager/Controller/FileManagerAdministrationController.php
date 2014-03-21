<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace FileManager\Controller;

use Zend\View\Model\ViewModel;
use FileManager\Model\Base as FileManagerBaseModel;

class FileManagerAdministrationController extends FileManagerBaseController
{
    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('files-manager-administration', 'list');
    }

    /**
     * List of files and directories
     */
    public function listAction()
    {
        return new ViewModel($this->getListFiles());
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel(array(
            'settingsForm' => parent::settingsForm('filemanager', 'files-manager-administration', 'settings')
        ));
    }

    /**
     * Delete selected files and directories
     */
    public function deleteAction()
    {
        return $this->deleteFiles();
    }

    /**
     * Add a new directory
     */
    public function addDirectoryAction()
    {
        $result = $this->addDirectory();

        return is_array($result)
            ? new ViewModel($result)
            : $result;
    }

    /**
     * Add a new file
     */
    public function addFileAction()
    {
        $result = $this->addFile();

        return is_array($result)
            ? new ViewModel($result)
            : $result;
    }

    /**
     * Edit a file or a directory
     */
    public function editAction()
    {
        $result = $this->editFile();

        return is_array($result)
            ? new ViewModel($result)
            : $result;
    }
}

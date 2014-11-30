<?php
namespace FileManager\Controller;

use Zend\View\Model\ViewModel;

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
        return new ViewModel([
            'settingsForm' => parent::settingsForm('filemanager', 'files-manager-administration', 'settings')
        ]);
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

        return is_array($result) ? new ViewModel($result) : $result;
    }

    /**
     * Add a new file
     */
    public function addFileAction()
    {
        $result = $this->addFile();

        return is_array($result) ? new ViewModel($result) : $result;
    }

    /**
     * Edit a file or a directory
     */
    public function editAction()
    {
        $result = $this->editFile();

        return is_array($result) ? new ViewModel($result) : $result;
    }
}
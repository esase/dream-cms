<?php
namespace FileManager\Controller;

use Zend\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;

class FileManagerEmbeddedController extends FileManagerBaseController
{
    /**
     * Layout name
     */
    protected $layout = 'layout/blank';

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('files-manager-embedded', 'list');
    }

    /**
     * List of files and directories
     */
    public function listAction()
    {
        return new ViewModel($this->getListFiles());
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
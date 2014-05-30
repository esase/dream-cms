<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace FileManager\Controller;

use Application\Controller\AbstractBaseController;
use FileManager\Model\Base as FileManagerBaseModel;
use FileManager\Event\Event as FileManagerEvent;
use User\Service\Service as UserService;

abstract class FileManagerBaseController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\Base');
        }

        return $this->model;
    }

    /**
     * Get user path
     *
     * @return string
     */
    protected function getUserPath()
    {
        return null != $this->getRequest()->getQuery('path', null)
            ? $this->getRequest()->getQuery('path')
            : FileManagerBaseModel::getHomeDirectoryName();
    }

    /**
     * Add a new file
     *
     * @retun array
     */
    protected function addFile()
    {
        $fileForm = null;

        // get a path
        $userPath = $this->getUserPath();

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

        // check the path
        if (false !== ($this->getModel()->getUserDirectory($userPath))) {
            // get a form
            $fileForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('FileManager\Form\File');

            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // make certain to merge the files info!
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );

                // fill the form with received values
                $fileForm->getForm()->setData($post, false);

                // save data
                if ($fileForm->getForm()->isValid()) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // add a new file
                    if (false === ($fileName =
                            $this->getModel()->addUserFile($this->params()->fromFiles('file'), $userPath))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Impossible add a new file. Check the received path permission'));
                    }
                    else {
                        // fire the add file event
                        FileManagerEvent::fireAddFileEvent($this->getModel()->getUserDirectory($userPath) . $fileName);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('File has been added'));
                    }

                    return $this->redirectTo($this->
                            params('controller'), 'add-file', array(), false, array('path' => $userPath));
                }
            }
        }

        return array(
            'fileForm' => $fileForm ? $fileForm->getForm() : null,
            'path' => $userPath,
            'userDirectories' => $userDirectories
        );
    }

    /**
     * Add a new directory
     *
     * @retun array
     */
    protected function addDirectory()
    {
        $directoryForm = null;

        // get a path
        $userPath = $this->getUserPath();

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

        // check the path
        if (false !== ($currentDirectory = $this->getModel()->getUserDirectory($userPath))) {
            // get a form
            $directoryForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('FileManager\Form\Directory')
                ->setPath($userPath)
                ->setModel($this->getModel());

            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // fill the form with received values
                $directoryForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($directoryForm->getForm()->isValid()) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // add a new directory
                    if (true === ($result = $this->getModel()->
                            addUserDirectory($directoryForm->getForm()->getData()['name'], $userPath))) {

                        // get a full path
                        $fullPath = $this->getModel()->
                                getUserDirectory($userPath) . $directoryForm->getForm()->getData()['name'];

                        // fire the add directory event
                        FileManagerEvent::fireAddDirectoryEvent($fullPath);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Directory has been added'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Impossible add a new directory. Check the received path permission'));
                    }

                    return $this->redirectTo($this->
                            params('controller'), 'add-directory', array(), false, array('path' => $userPath));
                }
            }
        }

        return array(
            'directoryForm' => $directoryForm ? $directoryForm->getForm() : null,
            'path' => $userPath,
            'userDirectories' => $userDirectories
        );
    }

    /**
     * Edit a file or a directory
     *
     * @retun array
     */
    protected function editFile()
    {
        $editForm = null;
        $isDirectory = false;

        // get a path
        $userPath = $this->getUserPath();
        $filePath = $this->getRequest()->getQuery('file_path', null);
        $fileName = null != $this->getRequest()->getQuery('slug', null)
            ? FileManagerBaseModel::slugifyFileName($this->getRequest()->getQuery('slug'), false, true)
            : null;

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

        // get absolute paths
        $userDirectory = $this->getModel()->getUserDirectory($userPath);
        $currentDirectory = $this->getModel()->getUserDirectory($filePath);

        // check the paths
        if (false !== $userDirectory && false !== $currentDirectory && $fileName) {
            // check the file name
            $fullFilePath = $currentDirectory . $fileName;
            $isDirectory = is_dir($fullFilePath);

            if ($fileName != '.' && $fileName != '..' && file_exists($fullFilePath)) {
                // get a form
                $editForm = $this->getServiceLocator()
                    ->get('Application\Form\FormManager')
                    ->getInstance('FileManager\Form\Edit')
                    ->setFileName($fileName)
                    ->setFullFilePath($currentDirectory)
                    ->setFullUserPath($userDirectory)
                    ->isDirectory($isDirectory)
                    ->setUserPath($userPath);

                $request  = $this->getRequest();

                // validate the form
                if ($request->isPost()) {
                    // fill the form with received values
                    $editForm->getForm()->setData($request->getPost(), false);

                    // save data
                    if ($editForm->getForm()->isValid()) {
                        // edit the file
                        if (false === ($newFileName = $this->getModel()->editFile($editForm->
                                getForm()->getData()['name'], $fullFilePath, $userDirectory, $isDirectory))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->
                                        translate((!$isDirectory ? 'Impossible edit selected file' : 'Impossible edit selected directory')));
                        }
                        else {
                            // fire the event
                            $isDirectory
                                ? FileManagerEvent::fireEditDirectoryEvent($fullFilePath, $userDirectory . $newFileName)
                                : FileManagerEvent::fireEditFileEvent($fullFilePath, $userDirectory . $newFileName);

                            $this->flashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->getTranslator()->
                                        translate((!$isDirectory ? 'The file has been edited' : 'The directory has been edited')));
                        }

                        return $this->redirectTo($this->params('controller'), 'edit', array(), false,
                                array('path' => $userPath, 'file_path' => $userPath, 'slug' => ($newFileName ? $newFileName : $fileName)));
                    }
                }
            }
        }

        return array(
            'isDirectory' => $isDirectory,
            'editForm' => $editForm ? $editForm->getForm() : null,
            'path' => $userPath,
            'filePath' => $filePath,
            'fileName' => $fileName,
            'userDirectories' => $userDirectories
        );
    }

    /**
     * Delete selected files and directories
     *
     * @return string
     */
    protected function deleteFiles()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($fileNames = $request->getPost('files', null))) {
                // process requested path
                $userPath = $this->getUserPath();

                // process files names
                foreach ($fileNames as $file) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // get a full path
                    $fullPath = $this->getModel()->getUserDirectory($userPath) . $file;
                    $isDirectory = is_dir($fullPath);

                    // delete the file or directory with nested files and dirs
                    if (true !== ($deleteResult = $this->getModel()->deleteUserFile($file, $userPath))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()
                            ->translate('Cannot delete some files or dirs. Check their permissions or existence'));

                        break;
                    }
        
                    // fire the event
                    $isDirectory
                        ? FileManagerEvent::fireDeleteDirectoryEvent($fullPath)
                        : FileManagerEvent::fireDeleteFileEvent($fullPath);
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected files and dirs have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo(null, null, array(), true);
    }

    /**
     * List action
     *
     * @retun array 
     */
    protected function getListFiles()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        $filters = array();

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('FileManager\Form\FileFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

        // get list of files and directories in specified directory
        $userPath = $this->getUserPath();

        $paginator = false;
        if (false !== ($currentDirectory = $this->getModel()->getUserDirectory($userPath))) {
            // get data
            $paginator = $this->getModel()->getFiles($currentDirectory,
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);
        }

        return array(
            'current_directory' => $currentDirectory,
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'path' => $userPath,
            'userDirectories' => $userDirectories,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        );
    }
}

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
    //TODO: when nothing found the action box is empty
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
     * Add a new directory
     *
     * @retun array
     */
    protected function addDirectory()
    {
        $userPath = null != $this->getRequest()->getQuery('path', null)
            ? $this->getRequest()->getQuery('path')
            : FileManagerBaseModel::getHomeDirectoryName();

        $directoryForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('FileManager\Form\Directory');

        $request  = $this->getRequest();

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

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
                            getUserDirectory($userPath) . '/' . $directoryForm->getForm()->getData()['name'];

                    $eventDescParams = UserService::isGuest()
                        ? array($fullPath)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $fullPath);

                    // event's description
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Directory added by guest'
                        : 'Event - Directory added by user';

                    FileManagerEvent::fireEvent(FileManagerEvent::FILE_MANAGER_ADD_DIRECTORY,
                            $fullPath, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

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

        return array(
            'directoryForm' => $directoryForm->getForm(),
            'path' => $userPath,
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
                $userPath = null != $this->getRequest()->getQuery('path', null)
                    ? $this->getRequest()->getQuery('path')
                    : FileManagerBaseModel::getHomeDirectoryName();

                // event's description
                $eventFileDesc = UserService::isGuest()
                    ? 'Event - File deleted by guest'
                    : 'Event - File deleteted by user';

                $eventDirDesc  = UserService::isGuest()
                    ? 'Event - Directory deleted by guest'
                    : 'Event - Directory deleteted by user';

                // process files names
                foreach ($fileNames as $file) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // get a full path
                    $fullPath = $this->getModel()->getUserDirectory($userPath) . '/' . $file;
                    $isDirectory = is_dir($fullPath);

                    // delete the file or directory with nested files and dirs
                    if (true !== ($deleteResult = $this->getModel()->deleteUserFile($file, $userPath))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()
                            ->translate('Cannot delete some files or dirs. Check their permissions or existence'));

                        break;
                    }

                    // fire the system event
                    $eventDescParams = UserService::isGuest()
                        ? array($fullPath)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $fullPath);

                    if ($isDirectory) {
                        $eventDesc = $eventDirDesc;
                        $eventName = FileManagerEvent::FILE_MANAGER_DELETE_DIRECTORY;
                    }
                    else {
                        $eventDesc = $eventFileDesc;
                        $eventName = FileManagerEvent::FILE_MANAGER_DELETE_FILE;
                    }

                    FileManagerEvent::fireEvent($eventName, $fullPath,
                            UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $userPath = null != $this->getRequest()->getQuery('path', null)
            ? $this->getRequest()->getQuery('path')
            : FileManagerBaseModel::getHomeDirectoryName();

        $paginator = false;
        if (false !== ($currentDirectory = $this->getModel()->getUserDirectory($userPath))) {
            // get data
            $paginator = $this->getModel()->getFiles($currentDirectory,
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);
        }

        return array(
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

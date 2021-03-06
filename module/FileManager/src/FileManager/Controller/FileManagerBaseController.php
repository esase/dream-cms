<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace FileManager\Controller;

use Application\Controller\ApplicationAbstractAdministrationController;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;

abstract class FileManagerBaseController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     *
     * @var \FileManager\Model\FileManagerBase
     */
    protected $model;

    /**
     * Get model
     *
     * @return \FileManager\Model\FileManagerBase
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\FileManagerBase');
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
     * @return array
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
                ->getInstance('FileManager\Form\FileManagerFile');

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
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // add a new file
                    if (false === ($fileName = $this->getModel()->addUserFile($this->params()->
                            fromFiles('file'), $userPath))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Impossible add a new file. Check the received path permission'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('File has been added'));
                    }

                    return $this->redirectTo($this->
                            params('controller'), 'add-file', [], false, ['path' => $userPath]);
                }
            }
        }

        return [
            'file_form' => $fileForm ? $fileForm->getForm() : null,
            'path' => $userPath,
            'user_directories' => $userDirectories
        ];
    }

    /**
     * Add a new directory
     *
     * @return array
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
                ->getInstance('FileManager\Form\FileManagerDirectory')
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
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // add a new directory
                    if (true === ($result = $this->getModel()->
                            addUserDirectory($directoryForm->getForm()->getData()['name'], $userPath))) {

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
                            params('controller'), 'add-directory', [], false, ['path' => $userPath]);
                }
            }
        }

        return [
            'directory_form' => $directoryForm ? $directoryForm->getForm() : null,
            'path' => $userPath,
            'user_directories' => $userDirectories
        ];
    }

    /**
     * Edit a file or a directory
     *
     * @return array
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
                    ->getInstance('FileManager\Form\FileManagerEdit')
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
                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission())) {
                            return $result;
                        }

                        // edit the file
                        if (false === ($newFileName = $this->getModel()->editFile($editForm->
                                getForm()->getData()['name'], $fullFilePath, $userDirectory, $isDirectory))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->
                                        translate((!$isDirectory ? 'Impossible edit selected file' : 'Impossible edit selected directory')));
                        }
                        else {
                            $this->flashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->getTranslator()->
                                        translate((!$isDirectory ? 'The file has been edited' : 'The directory has been edited')));
                        }

                        return $this->redirectTo($this->params('controller'), 'edit', [], false,
                                ['path' => $userPath, 'file_path' => $userPath, 'slug' => ($newFileName ? $newFileName : $fileName)]);
                    }
                }
            }
        }

        return [
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'is_directory' => $isDirectory,
            'edit_form' => $editForm ? $editForm->getForm() : null,
            'path' => $userPath,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'user_directories' => $userDirectories
        ];
    }

    /**
     * Delete selected files and directories
     *
     * @return string
     */
    protected function deleteFiles()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($fileNames = $request->getPost('files', null))) {
                // process requested path
                $userPath = $this->getUserPath();

                // process files names
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($fileNames as $file) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the file or directory with nested files and dirs
                    if (true !== ($deleteResult = $this->getModel()->deleteUserFile($file, $userPath))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()
                            ->translate('Cannot delete some files or dirs. Check their permissions or existence'));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected files and dirs have been deleted'
                        : 'The selected file or dir has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo(null, null, [], true);
    }

    /**
     * List action
     *
     * @return array
     */
    protected function getListFiles()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('FileManager\Form\FileManagerFileFilter');

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

        return [
            'current_directory' => $currentDirectory,
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'path' => $userPath,
            'user_directories' => $userDirectories,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ];
    }
}
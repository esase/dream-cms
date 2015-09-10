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
            'settings_form' => parent::settingsForm('filemanager', 'files-manager-administration', 'settings')
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
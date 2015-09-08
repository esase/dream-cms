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
namespace FileManager\View\Helper;

use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Zend\View\Helper\AbstractHelper;

class FileManagerDirectoryTree extends AbstractHelper
{
    /**
     * Tree's cookie lifetime
     *
     * @var integer
     */
    protected $treeCookieLifetimeDays = 30;

    /**
     * Generate a tree
     *
     * @param array|boolean $userDirectories
     * @param string $treeId
     * @param string $currentPath
     * @param array $filters
     * @param string $treeClass
     * @return string
     */
    public function __invoke($userDirectories = [], $treeId, $currentPath, array $filters = [], $treeClass = 'filetree')
    {
        $currentPath = $currentPath
            ? FileManagerBaseModel::processDirectoryPath($currentPath)
            : FileManagerBaseModel::getHomeDirectoryName();

        return $this->getView()->partial('file-manager/partial/directory-tree', [
            'id' => $treeId,
            'class' => $treeClass,
            'items' => $this->processDirectories($userDirectories, $currentPath, $filters),
            'cookie_lifetime' => $this->treeCookieLifetimeDays
        ]);
    }

    /**
     * Process directories
     *
     * @param array $userDirectories
     * @param string $currentPath
     * @param array $filters
     * @param string $parentDirectory
     * @return string
     */
    protected function processDirectories($userDirectories, $currentPath, $filters, $parentDirectory = null)
    {
        $content = null;

        foreach ($userDirectories as $directoryName => $subDirectories) {
            $directoryName = str_replace(PHP_EOL, null, $directoryName);

            // get a directory's url
            $path = $parentDirectory ? $parentDirectory . '/' . $directoryName : $directoryName;
            $urlParams = ['path' => $path] + $filters;
            $url = null;

            if ($currentPath != $path) {
                $url = $this->getView()->url('application/page', [
                    'controller' => $this->getView()->applicationRoute()->getParam('controller'),
                    'action' => $this->getView()->applicationRoute()->getParam('action')
                ], ['force_canonical' => true, 'query' => $urlParams]);
            }

            $content .= $this->getView()->partial('file-manager/partial/directory-tree-item-start', [
                'url' => $url,
                'name' => $directoryName
            ]);

            // process subdirectories
            if ($subDirectories) {
                $content .= $this->getView()->partial('file-manager/partial/directory-tree-item-children', [
                    'children' => $this->processDirectories($subDirectories, $currentPath, $filters, $path)
                ]);
            }

            $content .= $this->getView()->partial('file-manager/partial/directory-tree-item-end');
        }

        return $content;
    }
}
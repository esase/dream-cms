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
namespace Layout\Controller;

use Application\Event\ApplicationEvent;
use Layout\Model\LayoutBase as LayoutBaseModel;
use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class LayoutAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     *
     * @var \Layout\Model\LayoutAdministration
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Layout\Model\LayoutAdministration
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Layout\Model\LayoutAdministration');
        }

        return $this->model;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        // clear a layout caches
        $eventManager = ApplicationEvent::getEventManager();
        $eventManager->attach(ApplicationEvent::CHANGE_SETTINGS, function () {
            $this->getModel()->clearLayoutCaches();
        });

        return new ViewModel([
            'settings_form' => parent::settingsForm('layout', 'layouts-administration', 'settings')
        ]);
    }

    /**
     * List of not installed custom layouts
     */
    public function listNotInstalledAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get data
        $paginator = $this->getModel()->getNotInstalledLayouts($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return [
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ];
    }

    /**
     * Install selected layouts
     */
    public function installAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($layoutsIds = $request->getPost('layouts', null))) {
                // install selected layouts
                $installResult = false;
                $installedCount = 0;

                foreach ($layoutsIds as $layout) {
                    // get the layout's config
                    $layoutInstallConfig = $this->getModel()->getCustomLayoutInstallConfig($layout);

                    if (false === $layoutInstallConfig) {
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // install the layout
                    if (true !== ($installResult = $this->
                            getModel()->installCustomLayout($layout, $layoutInstallConfig))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($installResult ? $this->getTranslator()->translate($installResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $installedCount++;
                }

                if (true === $installResult) {
                    $message = $installedCount > 1
                        ? 'Selected layouts have been installed'
                        : 'The selected layout has been installed';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $this->redirectTo('layouts-administration', 'list-not-installed', [], true);
    }

    /**
     * Upload a new layout
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an layout form
        $layoutForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Layout\Form\Layout')
            ->setHost($host);

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $layoutForm->getForm()->setData($post, false);

            // upload a layout
            if ($layoutForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // upload the layout
                if (true === ($result =
                        $this->getModel()->uploadCustomLayout($layoutForm->getForm()->getData(), $host))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Layout has been uploaded'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('layouts-administration', 'upload');
            }
        }

        return new ViewModel([
            'layout_form' => $layoutForm->getForm()
        ]);
    }

    /**
     * Delete a layout
     */
    public function deleteAction()
    {
        $layoutName = $this->getSlug();

        // layout should be not installed
        if (null != ($layoutInfo = $this->getModel()->getLayoutInfo($layoutName)) 
                || false === ($layoutInstallConfig = $this->getModel()->getCustomLayoutInstallConfig($layoutName))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an layout form
        $layoutForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Layout\Form\Layout')
            ->setHost($host)
            ->setDeleteMode();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $layoutForm->getForm()->setData($request->getPost(), false);

            // delete a layout
            if ($layoutForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                if (true === ($result = $this->getModel()->
                        deleteCustomLayout($layoutName, $layoutForm->getForm()->getData(), $host))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Layout has been deleted'));

                    return $this->redirectTo('layouts-administration', 'list-not-installed');
                }

                $this->flashMessenger()
                    ->setNamespace('error')
                    ->addMessage($this->getTranslator()->translate($result));

                return $this->redirectTo('layouts-administration', 'delete', [
                    'slug' => $layoutName
                ]);
            }
        }

        return new ViewModel([
            'layout_name' => $layoutName,
            'layout_form' => $layoutForm->getForm()
        ]);
    }

    /**
     * Upload updates
     */
    public function uploadUpdatesAction()
    {
        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an layout form
        $layoutForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Layout\Form\Layout')
            ->setHost($host);

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $layoutForm->getForm()->setData($post, false);

            // upload updates
            if ($layoutForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                if (true === ($result = $this->
                        getModel()->uploadLayoutUpdates($layoutForm->getForm()->getData(), $host))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Updates of layout have been uploaded'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('layouts-administration', 'upload-updates');
            }
        }

        return new ViewModel([
            'layout_form' => $layoutForm->getForm()
        ]);
    }

    /**
     * List of installed layouts
     */
    public function listInstalledAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Layout\Form\LayoutFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getInstalledLayouts($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Uninstall selected layouts
     */
    public function uninstallAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($layoutsIds = $request->getPost('layouts', null))) {
                // uninstall selected layouts
                $uninstallResult = false;
                $uninstalledCount = 0;

                foreach ($layoutsIds as $layout) {
                    // get a layout info
                    if (null != ($layoutInfo = $this->getModel()->getLayoutInfo($layout))) {
                        if ($layoutInfo['type'] == LayoutBaseModel::LAYOUT_TYPE_SYSTEM) {
                            continue;
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // uninstall the layout
                        if (true !== ($uninstallResult = $this->getModel()->uninstallCustomLayout($layoutInfo))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(($uninstallResult ? $this->getTranslator()->translate($uninstallResult)
                                    : $this->getTranslator()->translate('Error occurred')));

                            break;
                        }
                    }

                    $uninstalledCount++;
                }

                if (true === $uninstallResult) {
                    $message = $uninstalledCount > 1
                        ? 'Selected layouts have been uninstalled'
                        : 'The selected layout has been uninstalled';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $this->redirectTo('layouts-administration', 'list-installed', [], true);
    }
}
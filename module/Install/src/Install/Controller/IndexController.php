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
namespace Install\Controller;

use Install\Form\Install as InstallForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Client as HttpClient;
use Exception;

class IndexController extends AbstractActionController
{
    /**
     * Model instance
     *
     * @var \Install\Model\InstallBase
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Install\Model\InstallBase
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()->get('Install\Model\InstallBase');
        }

        return $this->model;
    }

    /**
     * Send installation report
     *
     * @return void
     */
    protected function sendInstallationReport()
    {
        try {
            $config = $this->serviceLocator->get('config');
            $url = $config['support_url'] . '/' . $config['installation_report_script'];
            $url .= '?site=' . $this->serviceLocator->get('viewHelperManager')->get('serverUrl')->__invoke(true);

            $culConfig = [
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'curloptions' => [
                    CURLOPT_FOLLOWLOCATION => true
                ]
            ];

            $curlClient = new HttpClient($url, $culConfig);
            $curlClient->send();
        }
        catch(Exception $e)
        {}
    }

    /**
     * Index 
     */
    public function indexAction()
    {
        // check resources permissions
        if (true !== ($result = $this->checkResourcesPermissions())) {
            return $result;
        }

        // check PHP extensions
        if (true !== ($result = $this->checkPhpExtensions())) {
            return $result;
        }

        // check PHP disabled functions
        if (true !== ($result = $this->checkPhpDisabledFunctions())) {
            return $result;
        }

        // check PHP settings
        if (true !== ($result = $this->checkPhpSettings())) {
            return $result;
        }

        $errorMessage = null;
        $installForm = new InstallForm;
        $installForm->prepare();

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $installForm->setData($request->getPost());

            // finish the installation
            if ($installForm->isValid()) {
                $config = $this->serviceLocator->get('config');

                // show the finish page
                if (true === ($result = $this->getModel()->
                        install($installForm->getData(), $config['cms_name'], $config['cms_version']))) {

                    // show the finish page
                    $viewModel = new ViewModel;
                    $viewModel->setVariables([
                            'cron_command' => $this->getModel()->getCronCommandLine(),
                            'cron_jobs' => $this->getModel()->getCronJobs(),
                            'module_dir' => $this->getModel()->getInstallModuleDirPath()
                        ])
                        ->setTemplate('install/index/finish');

                    // send the installation report
                    $this->sendInstallationReport();

                    return $viewModel;
                }

                $errorMessage = $result;
            }
        }

        return new ViewModel([
            'error_message' => $errorMessage,
            'install_form' => $installForm
        ]);
    }

    /** 
     * Check PHP disabled functions
     *
     * @return boolean|\Zend\View\Model\ViewModel
     */
    protected function checkPhpDisabledFunctions()
    {
        // get disabled functions
        $functions = $this->getModel()->getPhpDisabledFunctions();

        // show PHP disabled functions layout
        if ($functions) {
            $viewModel = new ViewModel;
            $viewModel->setVariables([
                'functions' => $functions
            ])
            ->setTemplate('install/index/php-disabled-functions');

            return $viewModel;
        }

        return true;
    }

    /** 
     * Check PHP settings
     *
     * @return boolean|\Zend\View\Model\ViewModel
     */
    protected function checkPhpSettings()
    {
        // get not configured settings
        $settings = $this->getModel()->getNotConfiguredPhpSettings();

        // show PHP settings layout
        if ($settings) {
            $viewModel = new ViewModel;
            $viewModel->setVariables([
                'settings' => $settings
            ])
            ->setTemplate('install/index/php-settings');

            return $viewModel;
        }

        return true;
    }

    /** 
     * Check PHP extensions
     *
     * @return boolean|\Zend\View\Model\ViewModel
     */
    protected function checkPhpExtensions()
    {
        // get not installed extensions
        $extensions = $this->getModel()->getNotInstalledPhpExtensions();

        // show PHP extensions layout
        if ($extensions) {
            $viewModel = new ViewModel;
            $viewModel->setVariables([
                'extensions' => $extensions
            ])
            ->setTemplate('install/index/php-extensions');

            return $viewModel;
        }

        return true;
    }

    /** 
     * Check resources permissions
     *
     * @return boolean|\Zend\View\Model\ViewModel
     */
    protected function checkResourcesPermissions()
    {
        // get not writable resources
        $resources = $this->getModel()->getNotWritableResources();

        // show resources permissions layout
        if ($resources) {
            $viewModel = new ViewModel;
            $viewModel->setVariables([
                'resources' => $resources
            ])
            ->setTemplate('install/index/resources-permissions');

            return $viewModel;
        }

        return true;
    }
}
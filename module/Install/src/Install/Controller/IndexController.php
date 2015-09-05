<?php

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
     * @return boolean|object - ViewModel
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
     * @return boolean|object - ViewModel
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
     * @return boolean|object - ViewModel
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
     * @return boolean|object - ViewModel
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
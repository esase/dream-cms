<?php
namespace Install\Controller;

use Install\Form\Install as InstallForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
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
            $this->model = $this->getServiceLocator()->get('Install\Model\InstallBase');
        }

        return $this->model;
    }

    /**
     * Index 
     */
    public function indexAction()
    {
        // TODO: Show cron jobs on the finish page
        // TODO: generated password don't work

        // check resources permissions
        if (true !== ($result = $this->checkResourcesPermissions())) {
            return $result;
        }

        // check PHP extensions
        if (true !== ($result = $this->checkPhpExtensions())) {
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
                // show the finish page
                if (true === ($result = $this->getModel()->install($installForm->getData()))) {
                    // show the finish page
                    $viewModel = new ViewModel;
                    $viewModel->setTemplate('install/index/finish');

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
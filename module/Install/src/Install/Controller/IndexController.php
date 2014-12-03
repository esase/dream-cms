<?php
namespace Install\Controller;

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
        // check resources permissions
        if (true !== ($result = $this->checkResourcesPermissions())) {
            return $result;
        }
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
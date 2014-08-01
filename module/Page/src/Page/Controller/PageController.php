<?php
namespace Page\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Service\Service as ApplicationService;

class PageController extends AbstractActionController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Received path
     * @var array
     */
    protected $receivedPath = null;

    /**
     * Defaut layout
     */
    const DEFAULT_LAYOUT = 'layout_1_column';

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()->get('Page\Model\Page');
        }

        return $this->model;
    }

    /**
     * Index page
     */
    public function indexAction()
    {
        // get the last page name
        $pageName = array_filter($this->getReceivedPath());
        $pageName = end($pageName);

        // get current user's role and current site's language
        $userRole = ApplicationService::getCurrentUserIdentity()->role;
        $language = ApplicationService::getCurrentLocalization()['language'];

        // get the page info
        if (!$pageName || false == 
                ($pageInfo = $this->getModel()->getPageInfo($pageName, $userRole, $language))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }


        // get the page breadcrumb
        $breadcrumb = $this->getModel()->
                getPageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language);

        if (!$this->compareReceivedPath($breadcrumb)) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $viewModel = new ViewModel(array(
            'page' => $pageInfo,
            'breadcrumb' => $breadcrumb
        ));

        // set a custom page layout
        $viewModel->setTemplate('page/layout/' . (
            $pageInfo['layout'] ? $pageInfo['layout'] : self::DEFAULT_LAYOUT
        ));

        return $viewModel;
    }

    /**
     * Compare received path
     *
     * @param array $pages
     * @return boolean
     */
    protected function compareReceivedPath(array $pages)
    {
        $index = 0;
        $receivedPathCount = count($this->getReceivedPath());
        $pagesCount = count($pages);

        // compare the length of both array considering trailing slash
        if ($receivedPathCount != $pagesCount && $receivedPathCount - 1 != $pagesCount) {
            return false;
        }

        foreach ($pages as $page) {
            if ($this->getReceivedPath()[$index] != $page['slug']) {
                return false;
            }

            $index++;
        }

        return true;
    }

    /**
     * Get received path
     *
     * @return array
     */
    protected function getReceivedPath()
    {
        if ($this->receivedPath === null) {
            $this->receivedPath = explode('/', $this->params()->fromRoute('page_name'));
        }

        return $this->receivedPath; 
    }
}
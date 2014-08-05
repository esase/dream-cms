<?php
namespace Page\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;

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
     * Default page
     */
    const DEFAULT_PAGE = 'home';

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
        $receivedPath = $this->getReceivedPath();
        $pageName = end($receivedPath);

        // get current user's role and current site's language
        $userRole = UserIdentityService::getCurrentUserIdentity()->role;
        $language = LocalizationService::getCurrentLocalization()['language'];

        // get a page info
        if (!$pageName || false == ($pageInfo = $this->
                getModel()->getPageInfo($pageName, $userRole, $language))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the page breadcrumb
        $breadcrumb = $pageInfo['level'] > 2
            ? $this->getModel()->getPageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language)
            : [$pageInfo];

        if (!$this->compareReceivedPath($breadcrumb)) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // set the page variables
        $viewModel = new ViewModel([
            'page' => $pageInfo,
            'breadcrumb' => $breadcrumb
        ]);

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
        if (count($this->getReceivedPath()) != count($pages)) {
            return false;
        }

        $index = 0;
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
            // get a path from a route
            $this->receivedPath = $this->params()->fromRoute('page_name', null);
            $pathLength = strlen($this->receivedPath) - 1;

            // remove a last slash from the path
            if ($this->receivedPath[$pathLength] == '/') {
                $this->receivedPath = substr($this->receivedPath, 0, $pathLength);
            }

            // check some criterias
            null === $this->receivedPath
                ? $this->receivedPath = self::DEFAULT_PAGE // home page will be as a default page
                : ($this->receivedPath = $this->receivedPath == self::DEFAULT_PAGE ? null : $this->receivedPath);

            // convert the path to an array
            $this->receivedPath = explode('/', $this->receivedPath);
        }

        return $this->receivedPath; 
    }
}
<?php
namespace Page\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
        $userRole = $this->userIdentity()['role'];
        $language = $this->localization()['language'];

        // get a page info
        if (!$pageName || false == ($pageInfo = $this->
                getModel()->getPageInfo($pageName, $userRole, $language))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // check the extra page's checking
        if (!empty($pageInfo['check']) && false === eval($pageInfo['check'])) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the page's parents
        $pageParents = $pageInfo['level'] > 1
            ? $this->getModel()->getPageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language, false)
            : [$pageInfo];

        // get the page's breadcrumb
        if (false === ($breadcrumb = 
                $this->getPageBreadcrumb($pageParents, $pageInfo['level'] > 1))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // set the page variables
        $viewModel = new ViewModel([
            'page' => $pageInfo,
            'breadcrumb' => $breadcrumb
        ]);

        // set a custom page layout
        $viewModel->setTemplate(($pageInfo['layout'] 
                ? $pageInfo['layout'] : $pageInfo['default_layout']));

        return $viewModel;
    }

    /**
     * Get page breadcrumb
     *
     * @param array $pages
     * @param boolean $homeIncluded
     * @return array|boolean
     */
    protected function getPageBreadcrumb(array $pages, $homeIncluded = false)
    {
        // compare the count of paths
        if (count($this->getReceivedPath()) 
                != ($homeIncluded ? count($pages) - 1 : count($pages))) {

            return false;
        }

        $index = 0;
        $breadcrumb = [];

        foreach ($pages as $page) {
            // check the extra page's checking
            if (!empty($page['check']) && false === eval($page['check'])) {
                return false;
            }

            // skip the home page 
            if ($page['level'] > 1) {
                // compare received slugs 
                if ($this->getReceivedPath()[$index] != $page['slug']) {
                    return false;
                }

                $index++;
                $breadcrumb[] = $page;
            }
        }

        return $breadcrumb;
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
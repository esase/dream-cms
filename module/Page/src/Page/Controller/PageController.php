<?php
namespace Page\Controller;

use Application\Utility\ApplicationDisableSite as DisableSiteUtility;
use Page\Service\Page as PageService;
use Page\Utility\PagePrivacy as PagePrivacyUtility;
use Page\Event\PageEvent;
use Zend\Http\Response;
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
     * Home page
     * @var string
     */
    protected $homePage;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()->get('Page\Model\PageNestedSet');
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
                getModel()->getActivePageInfo($pageName, $userRole, $language))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the page's parents
        $pageParents = $pageInfo['level'] > 1
            ? $this->getModel()->getActivePageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language, false)
            : [$pageInfo];

        // get the page's breadcrumb
        if (false === ($breadcrumb = 
                $this->getPageBreadcrumb($pageParents, $pageInfo['level'] > 1))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // show a disabled site message
        if (true !== DisableSiteUtility::isAllowedViewSite()) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_503);

            // set the page variables
            $viewModel = new ViewModel([
                'message' => $this->applicationSetting('application_disable_site_message')
            ]);

            $viewModel->setTemplate($this->getModel()->getLayoutPath() . 'layout-disabled-site');
            $this->layout('layout/blank');
            return $viewModel;
        }

        // fire the page show event
        PageEvent::firePageShowEvent($pageInfo['slug'], $language);

        // check for redirect
        if ($pageInfo['redirect_url']) {
            $response = $this->redirect()->toUrl($pageInfo['redirect_url']);
            $response->setStatusCode(Response::STATUS_CODE_301);

            return $response;
        }

        $request = $this->getRequest();
        $widgetConnectionId = $this->params()->fromQuery('widget_connection', null);
        $widgetPosition = $this->params()->fromQuery('widget_position', null);

        PageService::setCurrentPage($pageInfo);

        // get only a specific widget info
        if ($request->isXmlHttpRequest()
                    && null !== $widgetConnectionId && null !== $widgetPosition) {

            // set the page variables
            $viewModel = new ViewModel([
                'page' => $pageInfo,
                'widget_connection' => $widgetConnectionId,
                'widget_position' => $widgetPosition
            ]);

            // set a custom page layout
            $viewModel->setTerminal(true)
                ->setTemplate($this->getModel()->getLayoutPath() . 'layout-ajax');

            return $viewModel;
        }

        // passing the current page info to the layout
        $this->layout()->setVariable('page', $pageInfo);

        // set the page variables
        $viewModel = new ViewModel([
            'page' => $pageInfo,
            'breadcrumb' => $breadcrumb
        ]);

        // set a custom page layout
        $viewModel->setTemplate($this->
                getModel()->getLayoutPath() . $pageInfo['layout']);

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
            // check the page's privacy
            if (false == ($result = PagePrivacyUtility::checkPagePrivacy($page['privacy']))) {
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
                ? $this->receivedPath = $this->getHomePage() // home page will be as a default page
                : ($this->receivedPath = $this->receivedPath == $this->getHomePage() ? null : $this->receivedPath);

            // convert the path to an array
            $this->receivedPath = explode('/', $this->receivedPath);
        }

        return $this->receivedPath; 
    }

    /**
     * Get home page
     *
     * @return string
     */
    protected function getHomePage()
    {
        if (!$this->homePage) {
            $this->homePage = $this->getServiceLocator()->get('Config')['home_page'];
        }

        return $this->homePage;
    }
}
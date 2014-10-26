<?php
namespace Page\Controller;

use Page\Model\Page as PageModel;
use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class PageAdministrationController extends ApplicationAbstractAdministrationController
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
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('pages-administration', 'list');
    }

    /**
     * View dependent pages
     */
    public function ajaxViewDependentPagesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', -1);
        $checkInStructure = $this->params()->fromQuery('check_structure', 1);

        return new ViewModel([
            'data' => $this->getModel()->getDependentPages($pageId, (int) $checkInStructure > 0)
        ]);
    }

    /**
     * Add selected system pages
     */
    public function addSystemPagesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($pagesIds = $request->getPost('pages', null))) {
                // get pages map
                if (null != ($systemPagesMap = $this->getModel()->getSystemPagesMap($pagesIds))) {
                    // get the page info
                    $parentPage = $this->getModel()->
                            getStructurePageInfo($this->params()->fromQuery('page_id', null), true, true);

                    // try to find the home page in system pages map
                    if (!$parentPage) {
                        $homePageName = $this->getServiceLocator()->get('Config')['home_page'];
                        $homePage = [];

                        foreach ($systemPagesMap as $index => $page) {
                            if ($page['slug'] == $homePageName) {
                                $homePage = $page;
                                unset($systemPagesMap[$index]);
                                break;
                            }
                        }

                        if (!$homePage) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Home page is not defined'));

                            return $this->redirectTo('pages-administration', 'system-pages', [], true);
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission())) {
                            return $result;
                        }

                        // add the home page
                        $homePageId = $this->getModel()->
                                addPage(PageModel::ROOT_LEVEl, PageModel::ROOT_RIGHT_KEY, null, true, $homePage);

                        if (!is_numeric($homePageId)) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate($homePageId));

                            return $this->redirectTo('pages-administration', 'system-pages', [], true);
                        }

                        // add sub pages
                        foreach ($systemPagesMap as $page) {
                            // check the permission and increase permission's actions track
                            if (true !== ($result = $this->aclCheckPermission())) {
                                return $result;
                            }

                            // get created page info
                            $homePage = $this->getModel()->getStructurePageInfo($homePageId);

                            $result = $this->getModel()->
                                addPage($homePage['level'], $homePage['right_key'], $homePage['id'], true, $page);

                            if (!is_numeric($result)) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate($result));
    
                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }
                        }
                    }
                    else {
                        // add pages
                        foreach ($systemPagesMap as $page) {
                            // check the permission and increase permission's actions track
                            if (true !== ($result = $this->aclCheckPermission())) {
                                return $result;
                            }

                            $result = $this->getModel()->
                                addPage($parentPage['level'], $parentPage['right_key'], $parentPage['id'], true, $page);

                            if (!is_numeric($result)) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate($result));
    
                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            // get updated parent page's info
                            $parentPage = $this->getModel()->getStructurePageInfo($parentPage['id']);
                        }
                    }

                    // clear cache
                    $this->getModel()->clearLanguageSensitivePageCaches();

                    $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Selected system pages have been added'));                    
                }
            }
        }

        // redirect back
        return $this->redirectTo('pages-administration', 'system-pages', [], true);
    }

    /**
     * Delete selected pages
     */
    public function deletePagesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($pagesIds = $request->getPost('pages', null))) {
                // delete selected pages
                foreach ($pagesIds as $pageId) {
                    // get a page's info
                    $pageInfo = $this->getModel()->getStructurePageInfo($pageId);

                    // page contains subpages or contains dependent pages should not be deleted
                    if (null == $pageInfo || ($pageInfo['dependent_page']
                                || $pageInfo['right_key'] - $pageInfo['left_key'] != 1)) {

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // delete the page
                    if (true !== ($deleteResult = $this->getModel()->deletePage($pageInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }
                }

                if (true === $deleteResult) {
                    // clear cache
                    $this->getModel()->clearLanguageSensitivePageCaches();

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected pages have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('pages-administration', 'list', [], true);
    }

    /**
     * System pages
     */
    public function systemPagesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if (null != ($page = $this->
                getModel()->getStructurePageInfo($pageId, true, true))) {

            $pageId = $page['id'];
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\SystemPageFilter');

        $filterForm->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getSystemPages($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'page_id' => $pageId
        ]);
    }

    /**
     * Edit page action
     */
    public function editPageAction()
    {
        // get the page info
        if (null == ($page = $this->
                getModel()->getStructurePageInfo($this->getSlug(), true, false, true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        // get a new selected page id
        $newPageId = $this->params()->fromQuery('page_id', null);

        // get the new page info
        if ($newPageId && $newPageId != $page['id']) {
            if (null == ($newPage =
                    $this->getModel()->getStructurePageInfo($newPageId, true))) {

                $newPageId = null;
            }
        }

        // get a page form
        $pageForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\Page')
            ->setModel($this->getModel())
            ->setPageId($page['id'])
            ->setSystemPage($page['system_page'])
            ->showMainMenu(!$page['disable_menu'])
            ->showSiteMap(!$page['disable_site_map'])
            ->showFooterMenu(!$page['disable_footer_menu'])
            ->showUserMenu(!$page['disable_user_menu']);

        // set default values
        $pageForm->getForm()->setData($page);
        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $pageForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($pageForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                echo '<br><br><br><br>update';
            }
        }

        return new ViewModel([
            'pageForm' => $pageForm->getForm(),
            'page_id' => $newPageId !== null ? $newPageId : $page['id']
        ]);
    }

    /**
     * Add a new custom page action
     */
    public function addCustomPageAction()
    {
        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if (null == ($page =
                $this->getModel()->getStructurePageInfo($pageId, true, true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        // get a page form
        $pageForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\Page')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $pageForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($pageForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new custom page
                $result = $this->getModel()->addPage($page['level'],
                        $page['right_key'], $page['id'], false, $pageForm->getForm()->getData());

                if (is_numeric($result)) {
                    // clear cache
                    $this->getModel()->clearLanguageSensitivePageCaches();

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Custom page has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('pages-administration', 'add-custom-page');
            }
        }

        return new ViewModel([
            'pageForm' => $pageForm->getForm(),
            'page_id' => $page['id']
        ]);
    }

    /**
     * List of pages
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if ($pageId !== null
                && null == ($page = $this->getModel()->getStructurePageInfo($pageId))) {

            // show the root page
            $pageId = null;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getStructurePages($pageId,
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'pages_map' => $this->getModel()->getPagesMap($this->getModel()->getCurrentLanguage()),
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'page_id' => $pageId
        ]);
    }
}
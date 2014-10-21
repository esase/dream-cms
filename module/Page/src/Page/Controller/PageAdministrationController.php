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
     * Add system pages
     *
     * @param array $systemPagesMap
     * @return string|boolean
     */
    protected function addSystemPages($systemPagesMap)
    {
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
                return 'Home page is not defined';
            }

            // check the permission and increase permission's actions track
            if (true !== ($result = $this->aclCheckPermission())) {
                return $result;
            }

            // add the home page
            $homePageId = $this->getModel()->
                    addPage(PageModel::ROOT_LEVEl, PageModel::ROOT_RIGHT_KEY, null, true, $homePage);

            if (!is_numeric($homePageId)) {
                return $homePageId;
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
                    return $result;
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
                    return $result;
                }

                // get updated parent page's info
                $parentPage = $this->getModel()->getStructurePageInfo($parentPage['id']);
            }
        }

        return true;
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
                    if (true === ($result = $this->addSystemPages($systemPagesMap))) {
                        //TODO:clear cache!
                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Selected system pages have been added'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));                        
                    }
                }
            }
        }

        // TODO: register the acl resource
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
        if ($pageId !== null &&
                null == ($page = $this->getModel()->getStructurePageInfo($pageId))) {

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
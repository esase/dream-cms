<?php
namespace Page\Controller;

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

        return new ViewModel([
            'data' => $this->getModel()->getDependentPages($pageId)
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
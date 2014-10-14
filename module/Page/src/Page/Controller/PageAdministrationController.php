<?php
namespace Page\Controller;

use Localization\Service\Localization as LocalizationService;
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
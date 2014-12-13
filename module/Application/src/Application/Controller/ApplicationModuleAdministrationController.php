<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;

class ApplicationModuleAdministrationController extends ApplicationAbstractAdministrationController
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
                ->getInstance('Application\Model\ApplicationModuleAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('modules-administration', 'list-installed');
    }

    /**
     * View module description
     */
    public function ajaxViewModuleDescriptionAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $module = $this->params()->fromQuery('id', -1);

        // get a module description
        if (null == ($descripion = $this->getModel()->getModuleDescription($module))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'descripion' => $descripion
        ]);
    }

    /**
     * List of not installed modules
     */
    public function listNotInstalledAction()
    {
        // TODO: ADD CHECKING OF DEPENDEDNT MODULES
        // TODO: MAKE AN INSTALLATION PROCESS

        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get data
        $paginator = $this->getModel()->getNotInstalledModules($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return [
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ];
    }

    /**
     * List of installed modules
     */
    public function listInstalledAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }
    }

    /**
     * Add new module
     */
    public function addNewAction()
    {
    }
}
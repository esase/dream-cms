<?php
namespace Membership\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use User\Service\Service as UserService;
use Membership\Model\Base as BaseMembershipModel;

class MembershipController extends AbstractBaseController
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
                ->getInstance('Membership\Model\Membership');
        }

        return $this->model;
    }

    /**
     * Membership levels list 
     */
    public function listAction()
    {
        if ($this->isGuest() || UserService::isDefaultUser()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // a paginator filters
        $filters = array(
            'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_STATUS_ACTIVE
        );

        // get list of active membership levels
        $paginator = $this->getModel()->getMembershipLevels($this->getPage(), 
                $this->getPerPage(), $this->getOrderBy('cost'), $this->getOrderType('asc'), $filters);

        $baseViewVariables = array(
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        );

        $viewModel = new ViewModel();

        // generate only the list of membership levels for ajax requests
        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setVariables($baseViewVariables);
            return $viewModel->setTemplate('membership/membership/units-list');
        }

        $viewModel->setVariables($baseViewVariables);
        return $viewModel;
    }
}
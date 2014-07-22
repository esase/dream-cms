<?php
namespace Membership\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use User\Service\Service as UserService;
use Membership\Model\Base as BaseMembershipModel;
use Application\Model\Acl as AclBaseModel;

class MembershipController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * User Model instance
     * @var object  
     */
    protected $userModel;

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
     * Get user model
     */
    protected function getUserModel()
    {
        if (!$this->userModel) {
            $this->userModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\Base');
        }

        return $this->userModel;
    }

    /**
     * Delete purchased membership level
     */
    public function ajaxDeletePurchasedMembershipAction()
    {
        if ($this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost() 
                && null !== ($membershipId = $request->getPost('id', null))) {

            // get a membership level info
            if (null !== ($connectionInfo = $this->getModel()->getMembershipConnectionInfo($membershipId, 
                    UserService::getCurrentUserIdentity()->user_id))) {

                // delete the membership level
                if (false !== ($deleteResult = 
                        $this->getModel()->deleteMembershipConnection($connectionInfo['id'], false))) {

                    if ($connectionInfo['active'] == 
                            BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE) {

                        // get a next membership connection
                        $nextConnection = $this->getModel()->
                                getMembershipConnectionFromQueue(UserService::getCurrentUserIdentity()->user_id);

                        $nextRoleId = $nextConnection
                            ? $nextConnection['role_id']
                            : AclBaseModel::DEFAULT_ROLE_MEMBER;

                        $nextRoleName = $nextConnection
                            ? $nextConnection['role_name']
                            : AclBaseModel::DEFAULT_ROLE_MEMBER_NAME;

                        // change the user's role 
                        if (true === ($result = $this->getUserModel()->editUserRole(UserService::
                                getCurrentUserIdentity()->user_id, $nextRoleId, $nextRoleName, $connectionInfo, true))) {

                            // activate the next membership connection
                            if ($nextConnection) {
                                $this->getModel()->activateMembershipConnection($nextConnection['id']);
                            }
                        }
                    }
                }
            }
        }

        $viewModel = new ViewModel(array(
            'user_levels' => $this->getModel()->
                    getAllUserMembershipConnections(UserService::getCurrentUserIdentity()->user_id, true)
        ));

        $viewModel->setTemplate('membership/membership/purchased-units-list');
        return $viewModel;
    }

    /**
     * Membership levels list 
     */
    public function listAction()
    {
        if (true !== ($result = $this->isAutorized())) {
            return $result;
        }

        // additional checking
        if (UserService::isDefaultUser()) {
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

        $extraViewVariables = array(
            'user_levels' => $this->getModel()->
                    getAllUserMembershipConnections(UserService::getCurrentUserIdentity()->user_id, true)
        );

        $viewModel->setVariables(array_merge($baseViewVariables, $extraViewVariables));
        return $viewModel;
    }
}
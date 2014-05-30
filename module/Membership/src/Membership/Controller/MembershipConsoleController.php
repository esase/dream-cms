<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Membership\Controller;

use Application\Controller\AbstractBaseConsoleController;
use Membership\Event\Event as MembershipEvent;
use User\Event\Event as UserEvent;
use Application\Model\Acl as AclBaseModel;
use User\Service\Service as UserService;

class MembershipConsoleController extends AbstractBaseConsoleController
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
                ->getInstance('Membership\Model\MembershipConsole');
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
     * Clean expired memberships connections
     */
    public function cleanExpiredMembershipsConnectionsAction()
    {
        $request = $this->getRequest();
        // get a list of expired memberships connections
        $deletedConnections = 0;
        if (null != ($expiredConnections = $this->getModel()->getExpiredMembershipsConnections())) {
            // process expired memberships connections
            foreach ($expiredConnections as $connectionInfo) {
                // delete the connection
                if (true === ($deleteResult = $this->getModel()->deleteMembershipConnection($connectionInfo['id']))) {
                    // fire the delete membership connection event
                    MembershipEvent::fireDeleteMembershipConnectionEvent($connectionInfo['id']);

                    // get a next membership connection
                    $nextMembershipConnection = $this->getModel()->getMembershipConnectionFromQueue($connectionInfo['user_id']);
                    $nextRoleId = $nextMembershipConnection
                        ? $nextMembershipConnection['role_id']
                        : AclBaseModel::DEFAULT_ROLE_MEMBER;

                    // change the user's role 
                    if (true === ($result = 
                            $this->getUserModel()->editUserRole($connectionInfo['user_id'], $nextRoleId))) {

                        // activate the next membership connection
                        if ($nextMembershipConnection && true === ($activateResult = $this->getModel()->
                                activateMembershipConnection($nextMembershipConnection['id'], $nextMembershipConnection['lifetime']))) {

                            // fire the activate membership connection event
                            MembershipEvent::fireActivateMembershipConnectionEvent($nextMembershipConnection['id']);
                        }

                        // fire the edit user role event
                        UserEvent::fireEditRoleEvent($connectionInfo, UserService::getAclRoles()[$nextRoleId], true);
                    }

                    $deletedConnections++;
                }
            }
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'All expired  membership connections have been deleted.' . "\n";
        }

        return $deletedConnections . ' membership connections  have been deleted.'. "\n";
    }
}
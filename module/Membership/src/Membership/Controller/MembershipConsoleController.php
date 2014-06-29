<?php
namespace Membership\Controller;

use Application\Controller\AbstractBaseConsoleController;
use Membership\Event\Event as MembershipEvent;
use User\Event\Event as UserEvent;
use Application\Model\Acl as AclBaseModel;
use User\Service\Service as UserService;
use Application\Utility\EmailNotification;

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

        $deletedConnections  = 0;
        $notifiedConnections = 0;

        // get a list of expired memberships connections
        if (null != ($expiredConnections = $this->getModel()->getExpiredMembershipsConnections())) {
            // process expired memberships connections
            foreach ($expiredConnections as $connectionInfo) {
                // delete the connection
                if (false === ($deleteResult = 
                        $this->getModel()->deleteMembershipConnection($connectionInfo['id']))) {

                    break;
                }

                // fire the delete membership connection event
                MembershipEvent::fireDeleteMembershipConnectionEvent($connectionInfo['id']);

                // get a next membership connection
                $nextConnection = $this->getModel()->getMembershipConnectionFromQueue($connectionInfo['user_id']);
                $nextRoleId = $nextConnection
                    ? $nextConnection['role_id']
                    : AclBaseModel::DEFAULT_ROLE_MEMBER;

                // change the user's role 
                if (true === ($result = $this->
                        getUserModel()->editUserRole($connectionInfo['user_id'], $nextRoleId))) {

                    // activate the next membership connection
                    if ($nextConnection && true === 
                            ($activateResult = $this->getModel()->activateMembershipConnection($nextConnection['id']))) {

                        // fire the activate membership connection event
                        MembershipEvent::fireActivateMembershipConnectionEvent($nextConnection['id']);
                    }

                    // fire the edit user role event
                    UserEvent::fireEditRoleEvent($connectionInfo, UserService::getAclRoles()[$nextRoleId], true);
                }

                $deletedConnections++;
            }
        }

        // get list of not notified memberships connections
        if ((int) UserService::getSetting('membership_expiring_send')) {
            if (null != ($notNotifiedConnections = $this->getModel()->getNotNotifiedMembershipsConnections())) {
                // process not notified memberships connections
                foreach ($notNotifiedConnections as $connectionInfo) {
                    if (false === ($markResult = 
                            $this->getModel()->markConnectionAsNotified($connectionInfo['id']))) {

                        break;
                    }

                    // send a notification about membership expiring
                    $notificationLanguage = $connectionInfo['language']
                        ? $connectionInfo['language'] // we should use the user's language
                        : UserService::getDefaultLocalization()['language'];

                    EmailNotification::sendNotification($connectionInfo['email'],
                            UserService::getSetting('membership_expiring_send_title', $notificationLanguage),
                            UserService::getSetting('membership_expiring_send_message', $notificationLanguage), array(
                                'find' => array(
                                    'RealName',
                                    'Role',
                                    'ExpireDate'
                                ),
                                'replace' => array(
                                    $connectionInfo['nick_name'],
                                    UserService::getServiceManager()->get('Translator')->
                                            translate($connectionInfo['role_name'], 'default', UserService::getLocalizations()[$notificationLanguage]['locale']),

                                    UserService::getServiceManager()->
                                            get('viewhelpermanager')->get('date')->__invoke($connectionInfo['expire_date'])
                                )
                            ));

                    $notifiedConnections++;
                }
            }
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'All expired  membership connections have been deleted.' . "\n";
        }

        $result  = $deletedConnections  . ' membership connections  have been deleted.'. "\n";
        $result .= $notifiedConnections . ' membership connections  have been notified.'. "\n";

        return $result;
    }
}
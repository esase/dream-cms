<?php
namespace User\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Application\Service\ApplicationSetting as SettingService;
use Acl\Service\Acl as AclService;
use Acl\Model\AclBase as AclBaseModel;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;

class UserAuthenticate
{
    /**
     * Is authenticate data valid
     *
     * @param string $nickName
     * @param string $password
     * @param array $errors
     * @return boolean|array
     */
    public static function isAuthenticateDataValid($nickName, $password, array &$errors)
    {
        UserIdentityService::getAuthService()
            ->getAdapter()
            ->setIdentity($nickName)
            ->setCredential($password);

        $result = UserIdentityService::getAuthService()->authenticate();

        if (!$result->isValid()) {
            UserEvent::fireLoginFailedEvent(AclBaseModel::DEFAULT_ROLE_GUEST, $nickName);
            $errors = $result->getMessages();
            return false;
        }

        // get the user info
        $userData = UserIdentityService::getAuthService()->getAdapter()->getResultRowObject([
            'user_id',
            'nick_name'
        ]);

        return [
            'user_id' => $userData->user_id,
            'nick_name' => $userData->nick_name
        ];
    }

    /**
     * Login user
     *
     * @param integer $userId
     * @param string $nickName
     * @param boolean $rememberMe
     * @return void
     */
    public static function loginUser($userId,  $nickName, $rememberMe)
    {
        $user = [];
        $user['user_id'] = $userId;

        // save user id
        UserIdentityService::getAuthService()->getStorage()->write($user);
        UserIdentityService::setCurrentUserIdentity(UserIdentityService::getUserInfo($userId));
        AclService::clearCurrentAcl();

        // fire the user login event
        UserEvent::fireLoginEvent($userId, $nickName);

        if ($rememberMe) {
            ServiceManagerService::getServiceManager()->
                    get('Zend\Session\SessionManager')->rememberMe((int) SettingService::getSetting('user_session_time'));
        }
    }
}
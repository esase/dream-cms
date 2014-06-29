<?php
namespace User\Event;

use Application\Event\AbstractEvent as AbstractEvent;
use User\Service\Service as UserService;
use Application\Utility\EmailNotification;

class Event extends AbstractEvent
{
    /**
     * Login event
     */
    const LOGIN = 'login_user';

    /**
     * Login failed event
     */
    const LOGIN_FAILED = 'login_user_failed';

    /**
     * Logout event
     */
    const LOGOUT = 'logout_user';

    /**
     * Get info by xmlrpc event
     */
    const GET_INFO_XMLRPC = 'get_user_info_via_xmlrpc';

    /**
     * Set timezone by xmlrpc event
     */
    const SET_TIMEZONE_XMLRPC = 'set_user_timezone_via_xmlrpc';

    /**
     * Disapprove event
     */
    const DISAPPROVE = 'disapprove_user';

    /**
     * Approve event
     */
    const APPROVE = 'approve_user';

    /**
     * Delete event
     */
    const DELETE = 'delete_user';

    /**
     * Add event
     */
    const ADD = 'add_user';

    /**
     * Edit event
     */
    const EDIT = 'edit_user';

    /**
     * Reset password event
     */
    const RESET_PASSWORD = 'reset_user_password';

    /**
     * Reset password request event
     */
    const RESET_PASSWORD_REQUEST = 'reset_user_password_request';

    /**
     * Edit role event
     */
    const EDIT_ROLE = 'edit_user_role';

    /**
     * Fire user password reset request event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     *      string slug
     * @param string $activateCode
     * @return void
     */
    public static function fireUserPasswordResetRequestEvent($userId, $userInfo, $activateCode)
    {
        self::fireEvent(self::RESET_PASSWORD_REQUEST, 
            $userId, $userId, 'Event - User requested password reset', array($userInfo['nick_name'], $userId));

        // send an email password reset notification
        EmailNotification::sendNotification($userInfo['email'],
            UserService::getSetting('user_reset_password_title'),
            UserService::getSetting('user_reset_password_message'), array(
                'find' => array(
                    'RealName',
                    'ConfirmationLink',
                    'ConfCode'
                ),
                'replace' => array(
                    $userInfo['nick_name'],
                    UserService::getServiceManager()->get('viewhelpermanager')->get('url')->__invoke('application', 
                            array('controller' => 'user', 'action' => 'password-reset', 'slug' => $userInfo['slug']), array('force_canonical' => true)),

                    $activateCode
                )
            ));
    }

    /**
     * Fire user password reset event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     * @param string $newPassword
     * @return void
     */
    public static function fireUserPasswordResetEvent($userId, $userInfo, $newPassword)
    {
        self::fireEvent(self::RESET_PASSWORD, 
                $userId, $userId, 'Event - User reseted password', array($userInfo['nick_name'], $userId));

        // send an email password reseted notification
        EmailNotification::sendNotification($userInfo['email'],
            UserService::getSetting('user_password_reseted_title'),
            UserService::getSetting('user_password_reseted_message'), array(
                'find' => array(
                    'RealName',
                    'Password'
                ),
                'replace' => array(
                    $userInfo['nick_name'],
                    $newPassword
                )
            ));
    }

    /**
     * Fire user edit event
     *
     * @param integer $userId
     * @return void
     */
    public static function fireUserEditEvent($userId, $selfEdit = false)
    {
        // event's description
        $eventDesc = $selfEdit
            ? 'Event - User edited'
            : (UserService::isGuest() ? 'Event - User edited by guest'
                    : 'Event - User edited by user');

        $eventDescParams = $selfEdit
            ? array(UserService::getCurrentUserIdentity()->nick_name, $userId)
            : (UserService::isGuest() ? array($userId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $userId));

        self::fireEvent(self::EDIT, $userId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }

    /**
     * Fire user add event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     * @return void
     */
    public static function fireUserAddEvent($userId, $userInfo = array())
    {
        // event's description
        $eventDesc = $userInfo
            ? 'Event - User registered'
            : (UserService::isGuest() ? 'Event - User added by guest'
                    : 'Event - User added by user');

        $eventDescParams = $userInfo
            ? array($userInfo['nick_name'], $userId)
            : (UserService::isGuest() ? array($userId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $userId));

        self::fireEvent(self::ADD, $userId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

        // send an email notification about register the new user
        if ($userInfo && (int) UserService::getSetting('user_registered_send')) {
            EmailNotification::sendNotification(UserService::getSetting('application_site_email'),
                UserService::getSetting('user_registered_title', UserService::getDefaultLocalization()['language']),
                UserService::getSetting('user_registered_message', UserService::getDefaultLocalization()['language']), array(
                    'find' => array(
                        'RealName',
                        'Email'
                    ),
                    'replace' => array(
                        $userInfo['nick_name'],
                        $userInfo['email']
                    )
                ));
        }
    }

    /**
     * Fire user delete event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     * @return void
     */
    public static function fireUserDeleteEvent($userId, $userInfo = array())
    {
        // event's description
        $eventDesc = !$userInfo
            ? 'Event - User deleted'
            : (UserService::isGuest() ? 'Event - User deleted by guest'
                    : 'Event - User deleted by user');

        $eventDescParams = !$userInfo
            ? array(UserService::getCurrentUserIdentity()->nick_name, $userId)
            : (UserService::isGuest() ? array($userId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $userId));

        self::fireEvent(self::DELETE, $userId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

        // send an email notification
        if ($userInfo && (int) UserService::getSetting('user_deleted_send')) {
            $notificationLanguage = $userInfo['language']
                ? $userInfo['language'] // we should use the user's language
                : UserService::getDefaultLocalization()['language'];

            EmailNotification::sendNotification($userInfo['email'],
                    UserService::getSetting('user_deleted_title', $notificationLanguage),
                    UserService::getSetting('user_deleted_message', $notificationLanguage), array(
                        'find' => array(
                            'RealName'
                        ),
                        'replace' => array(
                            $userInfo['nick_name']
                        )
                    ));
        }
    }

    /**
     * Fire user approve event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     * @param string $selfUserName
     * @return void
     */
    public static function fireUserApproveEvent($userId, $userInfo, $selfUserName = null)
    {
        // event's description
        $eventDesc = $selfUserName
            ? 'Event - User confirmed email'
            :  (UserService::isGuest() ? 'Event - User approved by guest' : 'Event - User approved by user');

        $eventDescParams = $selfUserName
            ? array($selfUserName)
            : (UserService::isGuest() 
                    ? array($userId) : array(UserService::getCurrentUserIdentity()->nick_name, $userId));

        self::fireEvent(self::APPROVE, $userId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

        // send an email notification
        if (!$selfUserName) {
            $notificationLanguage = $userInfo['language']
                ? $userInfo['language'] // we should use the user's language
                : UserService::getDefaultLocalization()['language'];

            EmailNotification::sendNotification($userInfo['email'],
                    UserService::getSetting('user_approved_title', $notificationLanguage),
                    UserService::getSetting('user_approved_message', $notificationLanguage), array(
                        'find' => array(
                            'RealName',
                            'Email'
                        ),
                        'replace' => array(
                            $userInfo['nick_name'],
                            $userInfo['email']
                        )
                    ));
        }
    }

    /**
     * Fire user disapprove event
     *
     * @param integer $userId
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     * @return void
     */
    public static function fireUserDisapproveEvent($userId, $userInfo)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - User disapproved by guest'
            : 'Event - User disapproved by user';

        $eventDescParams = UserService::isGuest()
            ? array($userId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $userId);

        self::fireEvent(self::DISAPPROVE, $userId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

        // send an email notification
        $notificationLanguage = $userInfo['language']
            ? $userInfo['language'] // we should use the user's language
            : UserService::getDefaultLocalization()['language'];

        EmailNotification::sendNotification($userInfo['email'],
                UserService::getSetting('user_disapproved_title', $notificationLanguage),
                UserService::getSetting('user_disapproved_message', $notificationLanguage), array(
                    'find' => array(
                        'RealName',
                        'Email'
                    ),
                    'replace' => array(
                        $userInfo['nick_name'],
                        $userInfo['email']
                    )
                ));
    }

    /**
     * Fire set user timezone via XmlRpc event
     *
     * @param integer $userId
     * @param string $userNickname
     * @return void
     */
    public static function fireSetTimezoneViaXmlRpcEvent($userId, $userNickname)
    {
        self::fireEvent(self::SET_TIMEZONE_XMLRPC, $userId, $userId, 'Event - Timezone set by user via XmlRpc', array($userNickname));
    }

    /**
     * Fire get user info via XmlRpc event
     *
     * @param integer $userId
     * @param string $userNick
     * @param integer $viewerId
     * @param string $viewerNick
     * @retun void
     */
    public static function fireGetUserInfoViaXmlRpcEvent($userId, $userNick, $viewerId, $viewerNick = null)
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - User\'s info was obtained by guest via XmlRpc'
            : 'Event - User\'s info was obtained by user via XmlRpc';

        $eventDescParams = UserService::isGuest()
            ? array($userNick)
            : array($viewerNick, $userNick);

        self::fireEvent(self::GET_INFO_XMLRPC, $viewerId, $userId, $eventDesc, $eventDescParams);
    }

    /**
     * Fire logout event
     *
     * @param integer $userId
     * @param string $userNickname
     * @return void
     */
    public static function fireLogoutEvent($userId, $userNickname)
    {
        self::fireEvent(self::LOGOUT, $userId, $userId, 'Event - User successfully logged out', array($userNickname));
    }

    /**
     * Fire login failed event
     *
     * @param integer $userId
     * @param string $userNickname
     * @return void
     */
    public static function fireLoginFailedEvent($userId, $userNickname)
    {
        self::fireEvent(self::LOGIN_FAILED, 0, $userId, 'Event - User login failed', array($userNickname));
    }

    /**
     * Fire login event
     *
     * @param integer $userId
     * @param string $userNickname
     * @return void
     */
    public static function fireLoginEvent($userId, $userNickname)
    {
        self::fireEvent(self::LOGIN, $userId, $userId, 'Event - User successfully logged in', array($userNickname));
    }

    /**
     * Fire edit role event
     *
     * @param array $user
     *      string language
     *      string email
     *      string nick_name
     *      integer user_id
     * @param string $roleName
     * @param boolean $isSystemEvent
     * @retun void
     */
    public static function fireEditRoleEvent($user, $roleName, $isSystemEvent = false)
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - User\'s role edited by the system'
            : (UserService::isGuest() 
                    ? 'Event - User\'s role edited by guest' : 'Event - User\'s role edited by user');

        $eventDescParams = $isSystemEvent
            ? array($user['user_id'])
            : (UserService::isGuest() 
                    ? array($user['user_id']) : array(UserService::getCurrentUserIdentity()->nick_name, $user['user_id']));

        self::fireEvent(self::EDIT_ROLE, $user['user_id'], self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);

        // send a notification
        if ((int) UserService::getSetting('user_role_edited_send')) {
            $notificationLanguage = $user['language']
                ? $user['language'] // we should use the user's language
                : UserService::getDefaultLocalization()['language'];

            EmailNotification::sendNotification($user['email'],
                UserService::getSetting('user_role_edited_title', $notificationLanguage),
                UserService::getSetting('user_role_edited_message', $notificationLanguage), array(
                    'find' => array(
                        'RealName',
                        'Role'
                    ),
                    'replace' => array(
                        $user['nick_name'],
                        UserService::getServiceManager()->get('Translator')->
                                translate($roleName, 'default', UserService::getLocalizations()[$notificationLanguage]['locale'])
                    )
                ));
        }
    }
}
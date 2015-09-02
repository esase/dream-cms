<?php
namespace User\Event;

use Application\Event\ApplicationAbstractEvent;
use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;
use Application\Service\ApplicationSetting as SettingService;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;

class UserEvent extends ApplicationAbstractEvent
{
    /**
     * Login event
     */
    const LOGIN = 'user_login';

    /**
     * Login failed event
     */
    const LOGIN_FAILED = 'user_login_failed';

    /**
     * Logout event
     */
    const LOGOUT = 'user_logout';

    /**
     * Get info by xmlrpc event
     */
    const GET_INFO_XMLRPC = 'user_get_info_via_xmlrpc';

    /**
     * Set timezone by xmlrpc event
     */
    const SET_TIMEZONE_XMLRPC = 'user_set_timezone_via_xmlrpc';

    /**
     * Disapprove event
     */
    const DISAPPROVE = 'user_disapprove';

    /**
     * Approve event
     */
    const APPROVE = 'user_approve';

    /**
     * Delete event
     */
    const DELETE = 'user_delete';

    /**
     * Add event
     */
    const ADD = 'user_add';

    /**
     * Edit event
     */
    const EDIT = 'user_edit';

    /**
     * Reset password event
     */
    const RESET_PASSWORD = 'user_reset_password';

    /**
     * Reset password request event
     */
    const RESET_PASSWORD_REQUEST = 'user_reset_password_request';

    /**
     * Edit role event
     */
    const EDIT_ROLE = 'user_edit_role';

    /**
     * Get info event
     */
    const GET_INFO = 'user_get_info';

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
            $userId, $userId, 'Event - User requested password reset', [$userInfo['nick_name'], $userId]);

        $resetPageUrl =  ServiceLocatorService::getServiceLocator()->
                get('viewHelperManager')->get('pageUrl')->__invoke('user-password-reset', [], null, true); 

        // send an email password reset notification
        EmailNotificationUtility::sendNotification($userInfo['email'],
            SettingService::getSetting('user_reset_password_title'),
            SettingService::getSetting('user_reset_password_message'), [
                'find' => [
                    'RealName',
                    'ConfirmationLink',
                    'ConfCode'
                ],
                'replace' => [
                    $userInfo['nick_name'],
                    ServiceLocatorService::getServiceLocator()->get('viewHelperManager')->get('url')->
                            __invoke('page', ['page_name' => $resetPageUrl, 'slug' => $userInfo['slug']], ['force_canonical' => true]),

                    $activateCode
                ]
            ], true);
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
                $userId, $userId, 'Event - User reseted password', [$userInfo['nick_name'], $userId]);

        // send an email password reset notification
        EmailNotificationUtility::sendNotification($userInfo['email'],
            SettingService::getSetting('user_password_reseted_title'),
            SettingService::getSetting('user_password_reseted_message'), [
                'find' => [
                    'RealName',
                    'Password'
                ],
                'replace' => [
                    $userInfo['nick_name'],
                    $newPassword
                ]
            ], true);
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
            : (UserIdentityService::isGuest() ? 'Event - User edited by guest' : 'Event - User edited by user');

        $eventDescParams = $selfEdit
            ? [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]
            : (UserIdentityService::isGuest() ? [$userId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]);

        self::fireEvent(self::EDIT, $userId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
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
    public static function fireUserAddEvent($userId, array $userInfo = [])
    {
        // event's description
        $eventDesc = $userInfo
            ? 'Event - User registered'
            : (UserIdentityService::isGuest() ? 'Event - User added by guest' : 'Event - User added by user');

        $eventDescParams = $userInfo
            ? [$userInfo['nick_name'], $userId]
            : (UserIdentityService::isGuest() ? [$userId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]);

        self::fireEvent(self::ADD, $userId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);

        // send an email notification about register the new user
        if ($userInfo && (int) SettingService::getSetting('user_registered_send')) {
            EmailNotificationUtility::sendNotification(SettingService::getSetting('application_site_email'),
                SettingService::getSetting('user_registered_title', LocalizationService::getDefaultLocalization()['language']),
                SettingService::getSetting('user_registered_message', LocalizationService::getDefaultLocalization()['language']), [
                    'find' => [
                        'RealName',
                        'Email'
                    ],
                    'replace' => [
                        $userInfo['nick_name'],
                        $userInfo['email']
                    ]
                ]);
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
    public static function fireUserDeleteEvent($userId, array $userInfo = [])
    {
        // event's description
        $eventDesc = !$userInfo
            ? 'Event - User deleted'
            : (UserIdentityService::isGuest() ? 'Event - User deleted by guest' : 'Event - User deleted by user');

        $eventDescParams = !$userInfo
            ? [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]
            : (UserIdentityService::isGuest() ? [$userId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]);

        self::fireEvent(self::DELETE, $userId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);

        // send an email notification
        if ($userInfo && (int) SettingService::getSetting('user_deleted_send')) {
            $notificationLanguage = $userInfo['language']
                ? $userInfo['language'] // we should use the user's language
                : LocalizationService::getDefaultLocalization()['language'];

            EmailNotificationUtility::sendNotification($userInfo['email'],
                    SettingService::getSetting('user_deleted_title', $notificationLanguage),
                    SettingService::getSetting('user_deleted_message', $notificationLanguage), [
                        'find' => [
                            'RealName'
                        ],
                        'replace' => [
                            $userInfo['nick_name']
                        ]
                    ]);
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
            :  (UserIdentityService::isGuest() ? 'Event - User approved by guest' : 'Event - User approved by user');

        $eventDescParams = $selfUserName
            ? [$selfUserName]
            : (UserIdentityService::isGuest() 
                    ? [$userId] : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId]);

        self::fireEvent(self::APPROVE, $userId, 
                UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);

        // send an email notification
        if (!$selfUserName) {
            $notificationLanguage = $userInfo['language']
                ? $userInfo['language'] // we should use the user's language
                : LocalizationService::getDefaultLocalization()['language'];

            EmailNotificationUtility::sendNotification($userInfo['email'],
                    SettingService::getSetting('user_approved_title', $notificationLanguage),
                    SettingService::getSetting('user_approved_message', $notificationLanguage), [
                        'find' => [
                            'RealName',
                            'Email'
                        ],
                        'replace' => [
                            $userInfo['nick_name'],
                            $userInfo['email']
                        ]
                    ]);
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
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - User disapproved by guest'
            : 'Event - User disapproved by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$userId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $userId];

        self::fireEvent(self::DISAPPROVE, $userId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);

        // send an email notification
        $notificationLanguage = $userInfo['language']
            ? $userInfo['language'] // we should use the user's language
            : LocalizationService::getDefaultLocalization()['language'];

        EmailNotificationUtility::sendNotification($userInfo['email'],
                SettingService::getSetting('user_disapproved_title', $notificationLanguage),
                SettingService::getSetting('user_disapproved_message', $notificationLanguage), [
                    'find' => [
                        'RealName',
                        'Email'
                    ],
                    'replace' => [
                        $userInfo['nick_name'],
                        $userInfo['email']
                    ]
                ]);
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
        self::fireEvent(self::SET_TIMEZONE_XMLRPC, 
                $userId, $userId, 'Event - Timezone set by user via XmlRpc', [$userNickname]);
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
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - User\'s info was obtained by guest via XmlRpc'
            : 'Event - User\'s info was obtained by user via XmlRpc';

        $eventDescParams = UserIdentityService::isGuest() 
            ? [$userNick] 
            : [$viewerNick, $userNick];

        self::fireEvent(self::GET_INFO_XMLRPC, $viewerId, $userId, $eventDesc, $eventDescParams);
    }

    /**
     * Fire get user info event
     *
     * @param integer $userId
     * @param string $userNick
     * @param integer $viewerId
     * @param string $viewerNick
     * @retun void
     */
    public static function fireGetUserInfoEvent($userId, $userNick, $viewerId, $viewerNick = null)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - User\'s info was obtained by guest'
            : 'Event - User\'s info was obtained by user';

        $eventDescParams = UserIdentityService::isGuest() 
            ? [$userNick] 
            : [$viewerNick, $userNick];

        self::fireEvent(self::GET_INFO, $viewerId, $userId, $eventDesc, $eventDescParams);
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
        self::fireEvent(self::LOGOUT, 
                $userId, $userId, 'Event - User successfully logged out', [$userNickname]);
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
        self::fireEvent(self::LOGIN_FAILED, 0, $userId, 'Event - User login failed', [$userNickname]);
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
        self::fireEvent(self::LOGIN, 
                $userId, $userId, 'Event - User successfully logged in', [$userNickname]);
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
            : (UserIdentityService::isGuest() 
                    ? 'Event - User\'s role edited by guest' : 'Event - User\'s role edited by user');

        $eventDescParams = $isSystemEvent
            ? [$user['user_id']]
            : (UserIdentityService::isGuest() 
                    ? [$user['user_id']] : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $user['user_id']]);

        self::fireEvent(self::EDIT_ROLE, $user['user_id'], self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);

        // send a notification
        if ((int) SettingService::getSetting('user_role_edited_send')) {
            $notificationLanguage = $user['language']
                ? $user['language'] // we should use the user's language
                : LocalizationService::getDefaultLocalization()['language'];

            EmailNotificationUtility::sendNotification($user['email'],
                SettingService::getSetting('user_role_edited_title', $notificationLanguage),
                SettingService::getSetting('user_role_edited_message', $notificationLanguage), [
                    'find' => [
                        'RealName',
                        'Role'
                    ],
                    'replace' => [
                        $user['nick_name'],
                        ServiceLocatorService::getServiceLocator()->get('Translator')->
                                translate($roleName, 'default', LocalizationService::getLocalizations()[$notificationLanguage]['locale'])
                    ]
                ]);
        }
    }
}
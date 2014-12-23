<?php
namespace Application\Event;

use User\Service\UserIdentity as UserIdentityService;

class ApplicationEvent extends ApplicationAbstractEvent
{
    /**
     * Change settings event
     */
    const CHANGE_SETTINGS = 'application_change_settings';

    /**
     * Clear cache event
     */
    const CLEAR_CACHE = 'application_clear_cache';

    /**
     * Send email notification
     */
    const SEND_EMAIL_NOTIFICATION = 'application_send_email_notification';

    /**
     * Install custom module event
     */
    const INSTALL_CUSTOM_MODULE = 'application_install_custom_module';

    /**
     * Uninstall custom module event
     */
    const UNINSTALL_CUSTOM_MODULE = 'application_uninstall_custom_module';

    /**
     * Fire send email notification event
     *
     * @param string $email
     * @param string $subject
     * @return object
     */
    public static function fireSendEmailNotificationEvent($email, $subject)
    {
        // event's description
        $eventDesc = 'Event - Email notification will be send';
        return self::fireEvent(self::SEND_EMAIL_NOTIFICATION, $email, self::getUserId(true), $eventDesc, [
            $email, 
            $subject
        ]);
    }

    /**
     * Fire clear cache event
     *
     * @param string $cache
     * @return void
     */
    public static function fireClearCacheEvent($cache)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Cache cleared by guest'
            : 'Event - Cache cleared by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$cache]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $cache];

        self::fireEvent(self::CLEAR_CACHE, 
                $cache, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire change settings event
     *
     * @param string $module
     * @return void
     */
    public static function fireChangeSettingsEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Settings were changed by guest'
            : 'Event - Settings were changed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::CHANGE_SETTINGS, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire install custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireInstallCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module installed by guest'
            : 'Event - Custom module installed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::INSTALL_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire uninstall custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireUninstallCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module uninstalled by guest'
            : 'Event - Custom module uninstalled by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::UNINSTALL_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
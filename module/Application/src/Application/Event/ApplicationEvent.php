<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     * Activate custom module event
     */
    const ACTIVATE_CUSTOM_MODULE = 'application_activate_custom_module';

    /**
     * Deactivate custom module event
     */
    const DEACTIVATE_CUSTOM_MODULE = 'application_deactivate_custom_module';

    /**
     * Upload custom module event
     */
    const UPLOAD_CUSTOM_MODULE = 'application_upload_custom_module';

    /**
     * Upload module updates event
     */
    const UPLOAD_MODULE_UPDATES = 'application_upload_module_updates';

    /**
     * Delete custom module event
     */
    const DELETE_CUSTOM_MODULE = 'application_delete_custom_module';

    /**
     * Fire send email notification event
     *
     * @param string $email
     * @param string $subject
     * @return void
     */
    public static function fireSendEmailNotificationEvent($email, $subject)
    {
        // event's description
        $eventDesc = 'Event - Email notification will be send';
        self::fireEvent(self::SEND_EMAIL_NOTIFICATION, $email, self::getUserId(true), $eventDesc, [
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

    /**
     * Fire activate custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireActivateCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module activated by guest'
            : 'Event - Custom module activated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::ACTIVATE_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire deactivate custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireDeactivateCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module deactivated by guest'
            : 'Event - Custom module deactivated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::DEACTIVATE_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire upload custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireUploadCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module uploaded by guest'
            : 'Event - Custom module uploaded by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::UPLOAD_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire upload module updates event
     *
     * @param string $module
     * @return void
     */
    public static function fireUploadModuleUpdatesEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - module updated by guest'
            : 'Event - module updated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::UPLOAD_MODULE_UPDATES, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete custom module event
     *
     * @param string $module
     * @return void
     */
    public static function fireDeleteCustomModuleEvent($module)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom module deleted by guest'
            : 'Event - Custom module deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$module]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $module];

        self::fireEvent(self::DELETE_CUSTOM_MODULE, 
                $module, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
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
 */
namespace Acl\Event;

use User\Service\UserIdentity as UserIdentityService;
use Application\Event\ApplicationAbstractEvent;

class AclEvent extends ApplicationAbstractEvent
{
    /**
     * Delete role event
     */
    const DELETE_ROLE = 'acl_delete_role';

    /**
     * Add role event
     */
    const ADD_ROLE = 'acl_add_role';

    /**
     * Edit role event
     */
    const EDIT_ROLE = 'acl_edit_role';
 
    /**
     * Allow resource event
     */
    const ALLOW_RESOURCE = 'acl_allow_resource';
 
    /**
     * Disallow resource event
     */
    const DISALLOW_RESOURCE = 'acl_disallow_resource';

    /**
     * Edit resource settings event
     */
    const EDIT_RESOURCE_SETTINGS = 'acl_edit_resource_settings';

    /**
     * Fire edit acl resource settings event
     *
     * @param integer $connectionId
     * @param integer $resourceId
     * @param integer $roleId
     * @param integer $userId
     *
     * @return void
     */
    public static function fireEditAclResourceSettingsEvent($connectionId, $resourceId, $roleId, $userId = 0)
    {
        // event's description
        $eventDesc = $userId
            ? (UserIdentityService::isGuest() ? 'Event - ACL user\'s resource settings edited by guest'
                    : 'Event - ACL user\'s resource settings edited by user')
            : (UserIdentityService::isGuest() ? 'Event - ACL resource settings edited by guest'
                    : 'Event - ACL resource settings edited by user');

        $eventDescParams = $userId
            ? (UserIdentityService::isGuest() ? [$roleId, $resourceId, $userId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $roleId, $resourceId, $userId])
            : (UserIdentityService::isGuest() ? [$roleId, $resourceId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $roleId, $resourceId]);

        self::fireEvent(self::EDIT_RESOURCE_SETTINGS, 
                $connectionId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire disallow acl resource event
     *
     * @param integer $resourceId
     * @param integer $roleId
     * @return void
     */
    public static function fireDisallowAclResourceEvent($resourceId, $roleId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - ACL resource disallowed by guest'
            : 'Event - ACL resource disallowed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$resourceId, $roleId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $resourceId, $roleId];

        self::fireEvent(self::DISALLOW_RESOURCE, 
                $resourceId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire allow acl resource event
     *
     * @param integer $resourceId
     * @param integer $roleId
     * @return void
     */
    public static function fireAllowAclResourceEvent($resourceId, $roleId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
           ? 'Event - ACL resource allowed by guest'
           : 'Event - ACL resource allowed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$resourceId, $roleId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $resourceId, $roleId];

        self::fireEvent(self::ALLOW_RESOURCE, 
                $resourceId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit acl role event
     *
     * @param integer $roleId
     * @return void
     */
    public static function fireEditAclRoleEvent($roleId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - ACL role edited by guest'
            : 'Event - ACL role edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$roleId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $roleId];

        self::fireEvent(self::EDIT_ROLE, 
                $roleId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire add acl role event
     *
     * @param integer $roleId
     * @return void
     */
    public static function fireAddAclRoleEvent($roleId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - ACL role added by guest'
            : 'Event - ACL role added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$roleId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $roleId];

        self::fireEvent(self::ADD_ROLE, 
                $roleId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete acl role event
     *
     * @param integer $roleId
     * @return void
     */
    public static function fireDeleteAclRoleEvent($roleId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - ACL role deleted by guest'
            : 'Event - ACL role deleteted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$roleId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $roleId];

        self::fireEvent(self::DELETE_ROLE, 
                $roleId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
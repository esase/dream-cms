<?php
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
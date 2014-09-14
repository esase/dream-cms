<?php
namespace Acl\Event;

use User\Service\Service as UserService;
use Application\Event\ApplicationAbstractEvent;

class AclEvent extends ApplicationAbstractEvent
{
    /**
     * Delete ACL role event
     */
    const DELETE_ACL_ROLE = 'delete_acl_role';

    /**
     * Add ACL role event
     */
    const ADD_ACL_ROLE = 'add_acl_role';

    /**
     * ACL role edit event
     */
    const EDIT_ACL_ROLE = 'edit_acl_role';
 
    /**
     * Allow ACL resource event
     */
    const ALLOW_ACL_RESOURCE = 'allow_acl_resource';
 
    /**
     * Disallow ACL resource event
     */
    const DISALLOW_ACL_RESOURCE = 'disallow_acl_resource';

    /**
     * Edit ACL resource settings event
     */
    const EDIT_ACL_RESOURCE_SETTINGS = 'edit_acl_resource_settings';

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
            ? (UserService::isGuest() ? 'Event - ACL user\'s resource settings edited by guest'
                    : 'Event - ACL user\'s resource settings edited by user')
            : (UserService::isGuest() ? 'Event - ACL resource settings edited by guest'
                    : 'Event - ACL resource settings edited by user');

        $eventDescParams = $userId
            ? (UserService::isGuest() ? array($roleId, $resourceId, $userId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $roleId, $resourceId, $userId))
            : (UserService::isGuest() ? array($roleId, $resourceId)
                    : array(UserService::getCurrentUserIdentity()->nick_name, $roleId, $resourceId));

        self::fireEvent(self::EDIT_ACL_RESOURCE_SETTINGS, 
                $connectionId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - ACL resource disallowed by guest'
            : 'Event - ACL resource disallowed by user';

        $eventDescParams = UserService::isGuest()
            ? array($resourceId, $roleId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $resourceId, $roleId);

        self::fireEvent(self::DISALLOW_ACL_RESOURCE, 
                $resourceId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
           ? 'Event - ACL resource allowed by guest'
           : 'Event - ACL resource allowed by user';

        $eventDescParams = UserService::isGuest()
            ? array($resourceId, $roleId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $resourceId, $roleId);

        self::fireEvent(self::ALLOW_ACL_RESOURCE, 
                $resourceId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - ACL role edited by guest'
            : 'Event - ACL role edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($roleId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $roleId);

        self::fireEvent(self::EDIT_ACL_ROLE, 
                $roleId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - ACL role added by guest'
            : 'Event - ACL role added by user';

        $eventDescParams = UserService::isGuest()
            ? array($roleId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $roleId);

        self::fireEvent(self::ADD_ACL_ROLE, 
                $roleId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - ACL role deleted by guest'
            : 'Event - ACL role deleteted by user';

        $eventDescParams = UserService::isGuest()
            ? array($roleId)
            : array(UserService::getCurrentUserIdentity()->nick_name, $roleId);

        self::fireEvent(self::DELETE_ACL_ROLE, 
                $roleId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }
}
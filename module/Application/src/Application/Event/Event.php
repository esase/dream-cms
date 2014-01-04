<?php

namespace Application\Event;
use Zend\EventManager\EventManager as EventManager;

class Event
{
    /**
     * Event manager
     * @var object
     */
    protected static $eventManager;

    /**
     * Application get localizations by xmlrpc event
     */
    const APPLICATION_GET_LOCALIZATIONS = 'get_localizations_via_xmlrpc';

    /**
     * Application change settings event
     */
    const APPLICATION_CHANGE_SETTINGS = 'change_settings';

    /**
     * Application ACL role delete event
     */
    const APPLICATION_DELETE_ACL_ROLE = 'delete_acl_role';

    /**
     * Application ACL role add event
     */
    const APPLICATION_ADD_ACL_ROLE = 'add_acl_role';

    /**
     * Application ACL role edit event
     */
    const APPLICATION_EDIT_ACL_ROLE = 'edit_acl_role';
 
    /**
     * Application ACL resource allow event
     */
    const APPLICATION_ALLOW_ACL_RESOURCE = 'allow_acl_resource';
 
    /**
     * Application ACL resource disallow event
     */
    const APPLICATION_DISALLOW_ACL_RESOURCE = 'disallow_acl_resource';

    /**
     * Application ACL resource edit settings event
     */
    const APPLICATION_EDIT_ACL_RESOURCE_SETTINGS = 'edit_acl_resource_settings';

    /**
     * Application clear cache event
     */
    const APPLICATION_CLEAR_CACHE = 'clear_cache';

    /**
     * Fire event
     *
     * @param string $event
     * @param integer $objectid
     * @param integer $userId
     * @param string $description
     * @param array $descriptionParams
     * @return void
     */
    public static function fireEvent($event, $objectid, $userId, $description, array $descriptionParams = array())
    {
        self::getEventManager()->trigger($event, __METHOD__, array(
            'object_id' => $objectid,
            'description' => $description,
            'description_params' => $descriptionParams,
            'user_id' => $userId
        ));
    }

    /**
     * Get instance of event manager
     *
     * @return object
     */
    public static function getEventManager()
    {
        if (self::$eventManager) {
            return self::$eventManager;
        }

        self::$eventManager = new EventManager();
        return self::$eventManager;
    }
}
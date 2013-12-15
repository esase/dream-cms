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
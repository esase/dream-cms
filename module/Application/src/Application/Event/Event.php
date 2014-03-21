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
     * Get localizations by xmlrpc event
     */
    const GET_LOCALIZATIONS = 'get_localizations_via_xmlrpc';

    /**
     * Change settings event
     */
    const CHANGE_SETTINGS = 'change_settings';

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
     * Clear cache event
     */
    const CLEAR_CACHE = 'clear_cache';

    /**
     * Send email notification
     */
    const SEND_EMAIL_NOTIFICATION = 'send_email_notification';

    /**
     * Fire event
     *
     * @param string $event
     * @param integer|string $objectid
     * @param integer $userId
     * @param string $description
     * @param array $descriptionParams
     * @return object
     */
    public static function fireEvent($event, $objectid, $userId, $description, array $descriptionParams = array())
    {
        return self::getEventManager()->trigger($event, __METHOD__, array(
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
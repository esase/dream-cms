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
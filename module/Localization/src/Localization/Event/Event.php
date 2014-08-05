<?php
namespace Localization\Event;

use User\Service\Service as UserService;

class Event extends AbstractEvent
{
    /**
     * Get localizations by xmlrpc event
     */
    const GET_LOCALIZATIONS = 'get_localizations_via_xmlrpc';

    /**
     * Fire get localizations via XmlRpc event
     *
     * @return void
     */
    public static function fireGetLocalizationsViaXmlRpcEvent()
    {
        // event's description
        $eventDesc = UserService::isGuest()
            ? 'Event - Localizations were got by guest via XmlRpc'
            : 'Event - Localizations were got by user via XmlRpc';

        $eventDescParams = UserService::isGuest()
            ? array()
            : array(UserService::getCurrentUserIdentity()->nick_name);

        self::fireEvent(self::GET_LOCALIZATIONS, 
                0, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }
}
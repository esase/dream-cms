<?php
namespace Localization\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class LocalizationEvent extends ApplicationAbstractEvent
{
    /**
     * Get localizations by xmlrpc event
     */
    const GET_LOCALIZATIONS = 'localization_get_localizations_via_xmlrpc';

    /**
     * Delete localization event
     */
    const DELETE = 'localization_delete';

    /**
     * Fire get localizations via XmlRpc event
     *
     * @return void
     */
    public static function fireGetLocalizationsViaXmlRpcEvent()
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Localizations were got by guest via XmlRpc'
            : 'Event - Localizations were got by user via XmlRpc';

        $eventDescParams = UserIdentityService::isGuest()
            ? []
            : [UserIdentityService::getCurrentUserIdentity()['nick_name']];

        self::fireEvent(self::GET_LOCALIZATIONS, 
                0, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete localization event
     *
     * @param string $language
     * @return void
     */
    public static function fireDeleteLocalizationEvent($language)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Localization deleted by guest'
            : 'Event - Localization deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$language]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $language];

        self::fireEvent(self::DELETE, 
                $language, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
<?php
namespace Layout\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class LayoutEvent extends ApplicationAbstractEvent
{
    /**
     * Delete layout event
     */
    const DELETE = 'layout_delete';

    /**
     * Fire delete layout event
     *
     * @param integer $layoutId
     * @return void
     */
    public static function fireDeleteLayoutEvent($layoutId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Layout deleted by guest'
            : 'Event - Layout deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layoutId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layoutId];

        self::fireEvent(self::DELETE, 
                $layoutId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
<?php
namespace Layout\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class LayoutEvent extends ApplicationAbstractEvent
{
    /**
     * Delete custom layout event
     */
    const DELETE = 'layout_delete';

    /**
     * Install custom layout event
     */
    const INSTALL = 'layout_install';

    /**
     * Upload custom layout event
     */
    const UPLOAD = 'layout_upload';

    /**
     * Fire upload custom layout event
     *
     * @param string $layout
     * @return void
     */
    public static function fireUploadCustomLayoutEvent($layout)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom layout uploaded by guest'
            : 'Event - Custom layout uploaded by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layout]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layout];

        self::fireEvent(self::UPLOAD, 
                $layout, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire install custom module event
     *
     * @param string $layout
     * @return void
     */
    public static function fireInstallCustomLayoutEvent($layout)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom layout installed by guest'
            : 'Event - Custom layout installed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layout]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layout];

        self::fireEvent(self::INSTALL, 
                $layout, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete custom layout event
     *
     * @param integer $layoutId
     * @return void
     */
    public static function fireDeleteCustomLayoutEvent($layoutId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom layout deleted by guest'
            : 'Event - Custom layout deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layoutId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layoutId];

        self::fireEvent(self::DELETE, 
                $layoutId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
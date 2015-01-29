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
     * Uninstall custom layout event
     */
    const UNINSTALL = 'layout_uninstall';

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
     * @param string $layoutName
     * @return void
     */
    public static function fireDeleteCustomLayoutEvent($layoutName)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom layout deleted by guest'
            : 'Event - Custom layout deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layoutName]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layoutName];

        self::fireEvent(self::DELETE, 
                $layoutName, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
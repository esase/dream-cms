<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     * Upload layout updates event
     */
    const UPLOAD_UPDATES = 'layout_upload_updates';

    /**
     * Fire uninstall custom layout event
     *
     * @param string $layout
     * @return void
     */
    public static function fireUninstallCustomLayoutEvent($layout)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Custom layout uninstalled by guest'
            : 'Event - Custom layout uninstalled by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layout]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layout];

        self::fireEvent(self::UNINSTALL, 
                $layout, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire upload layout updates event
     *
     * @param string $layout
     * @return void
     */
    public static function fireUploadLayoutUpdatesEvent($layout)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - layout updated by guest'
            : 'Event - layout updated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$layout]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $layout];

        self::fireEvent(self::UPLOAD_UPDATES, 
                $layout, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

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
     * Fire install custom layout event
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
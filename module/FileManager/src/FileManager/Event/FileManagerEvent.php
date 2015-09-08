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
namespace FileManager\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class FileManagerEvent extends ApplicationAbstractEvent
{
    /**
     * Delete file event
     */
    const DELETE_FILE = 'file_manager_delete_file';

    /**
     * Delete directory event
     */
    const DELETE_DIRECTORY = 'file_manager_delete_directory';

    /**
     * Add  directory event
     */
    const ADD_DIRECTORY = 'file_manager_add_directory';

    /**
     * Add file event
     */
    const ADD_FILE = 'file_manager_add_file';

    /**
     * Edit file event
     */
    const EDIT_FILE = 'file_manager_edit_file';

    /**
     * Edit directory event
     */
    const EDIT_DIRECTORY = 'file_manager_edit_directory';

    /**
     * Fire edit directory event
     *
     * @param string $path
     * @param string $newPath
     * @return void
     */
    public static function fireEditDirectoryEvent($path, $newPath)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Directory edited by guest'
            : 'Event - Directory edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path, $newPath]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path, $newPath];

        self::fireEvent(self::EDIT_DIRECTORY, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit file event
     *
     * @param string $path
     * @param string $newPath
     * @return void
     */
    public static function fireEditFileEvent($path, $newPath)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - File edited by guest'
            : 'Event - File edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path, $newPath]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path, $newPath];

        self::fireEvent(self::EDIT_FILE, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire add file event
     *
     * @param string $path
     * @return void
     */
    public static function fireAddFileEvent($path)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - File added by guest'
            : 'Event - File added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path];

        self::fireEvent(self::ADD_FILE, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire add directory event
     *
     * @param string $path
     * @return void
     */
    public static function fireAddDirectoryEvent($path)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Directory added by guest'
            : 'Event - Directory added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path];

        self::fireEvent(self::ADD_DIRECTORY, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete directory event
     *
     * @param string $path
     * @param boolean $isSystemEvent
     * @return void
     */
    public static function fireDeleteDirectoryEvent($path, $isSystemEvent = false)
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - Directory deleted by the system'
            : (UserIdentityService::isGuest() ? 'Event - Directory deleted by guest'  : 'Event - Directory deleted by user');

        $eventDescParams = $isSystemEvent
            ? [$path]
            : (UserIdentityService::isGuest() ? [$path]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path]);

        self::fireEvent(self::DELETE_DIRECTORY, $path, self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete file event
     *
     * @param string $path
     * @return void
     */
    public static function fireDeleteFileEvent($path)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - File deleted by guest'
            : 'Event - File deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path];

        self::fireEvent(self::DELETE_FILE, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
<?php
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
     * @pram boolean $isSystemEvent
     * @return void
     */
    public static function fireDeleteDirectoryEvent($path, $isSystemEvent = false)
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - Directory deleted by the system'
            : (UserIdentityService::isGuest() ? 'Event - Directory deleted by guest'  : 'Event - Directory deleteted by user');

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
            : 'Event - File deleteted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$path]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $path];

        self::fireEvent(self::DELETE_FILE, 
                $path, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}
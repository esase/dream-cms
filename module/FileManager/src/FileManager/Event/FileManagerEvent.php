<?php
namespace FileManager\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\Service as UserService;

class FileManagerEvent extends ApplicationAbstractEvent
{
    /**
     * Delete file event
     */
    const DELETE_FILE = 'delete_file';

    /**
     * Delete directory event
     */
    const DELETE_DIRECTORY = 'delete_directory';

    /**
     * Add  directory event
     */
    const ADD_DIRECTORY = 'add_directory';

    /**
     * Add file event
     */
    const ADD_FILE = 'add_file';

    /**
     * Edit file event
     */
    const EDIT_FILE = 'edit_file';

    /**
     * Edit directory event
     */
    const EDIT_DIRECTORY = 'edit_directory';

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
        $eventDesc = UserService::isGuest()
            ? 'Event - Directory edited by guest'
            : 'Event - Directory edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($path, $newPath)
            : array(UserService::getCurrentUserIdentity()->nick_name, $path, $newPath);

        self::fireEvent(self::EDIT_DIRECTORY, 
                $path, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - File edited by guest'
            : 'Event - File edited by user';

        $eventDescParams = UserService::isGuest()
            ? array($path, $newPath)
            : array(UserService::getCurrentUserIdentity()->nick_name, $path, $newPath);

        self::fireEvent(self::EDIT_FILE, 
                $path, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - File added by guest'
            : 'Event - File added by user';

        $eventDescParams = UserService::isGuest()
            ? array($path)
            : array(UserService::getCurrentUserIdentity()->nick_name, $path);

        self::fireEvent(self::ADD_FILE, 
                $path, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
        $eventDesc = UserService::isGuest()
            ? 'Event - Directory added by guest'
            : 'Event - Directory added by user';

        $eventDescParams = UserService::isGuest()
            ? array($path)
            : array(UserService::getCurrentUserIdentity()->nick_name, $path);

        self::fireEvent(self::ADD_DIRECTORY, 
                $path, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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
            : (UserService::isGuest() ? 'Event - Directory deleted by guest' 
                    : 'Event - Directory deleteted by user');

        $eventDescParams = $isSystemEvent
            ? array($path)
            : (UserService::isGuest() ? array($path) 
                    : array(UserService::getCurrentUserIdentity()->nick_name, $path));

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
        $eventDesc = UserService::isGuest()
            ? 'Event - File deleted by guest'
            : 'Event - File deleteted by user';

        $eventDescParams = UserService::isGuest()
            ? array($path)
            : array(UserService::getCurrentUserIdentity()->nick_name, $path);

        self::fireEvent(self::DELETE_FILE, 
                $path, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
    }
}
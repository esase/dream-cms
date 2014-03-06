<?php

namespace FileManager\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
{
    /**
     * File delete event
     */
    const FILE_MANAGER_DELETE_FILE = 'delete_file';

    /**
     * Directory delete event
     */
    const FILE_MANAGER_DELETE_DIRECTORY = 'delete_directory';

    /**
     * Directory add  event
     */
    const FILE_MANAGER_ADD_DIRECTORY = 'add_directory';
}
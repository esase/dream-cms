<?php

namespace FileManager\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
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
}
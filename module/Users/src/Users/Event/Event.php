<?php

namespace Users\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
{
    /**
     * User login event
     */
    const USER_LOGIN = 'user.login';

    /**
     * User login failed event
     */
    const USER_LOGIN_FAILED = 'user.login.failed';

    /**
     * User logout event
     */
    const USER_LOGOUT = 'user.logout';
}
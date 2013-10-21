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

    /**
     * User get info by xmlrpc event
     */
    const USER_GET_INFO_XMLRPC = 'user.get.info.xmlrpc';

    /**
     * User set timezone by xmlrpc event
     */
    const USER_SET_TIMEZONE_XMLRPC = 'user.set.timezone.xmlrpc';
}
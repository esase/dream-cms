<?php

namespace Users\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
{
    /**
     * User login event
     */
    const USER_LOGIN = 'user_login';

    /**
     * User login failed event
     */
    const USER_LOGIN_FAILED = 'user_login_failed';

    /**
     * User logout event
     */
    const USER_LOGOUT = 'user_logout';

    /**
     * User get info by xmlrpc event
     */
    const USER_GET_INFO_XMLRPC = 'get_user_info_via_xmlrpc';

    /**
     * User set timezone by xmlrpc event
     */
    const USER_SET_TIMEZONE_XMLRPC = 'set_user_timezone_via_xmlrpc';
}
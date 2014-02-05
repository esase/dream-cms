<?php

namespace User\Event;

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

    /**
     * User disapprove event
     */
    const USER_DISAPPROVE = 'user_disapprove';

    /**
     * User approve event
     */
    const USER_APPROVE = 'user_approve';

    /**
     * User delete event
     */
    const USER_DELETE = 'user_delete';

    /**
     * User add event
     */
    const USER_ADD = 'user_add';

    /**
     * User edit event
     */
    const USER_EDIT = 'user_edit';

    /**
     * User password reset event
     */
    const USER_PASSWORD_RESET = 'user_password_reset';

    /**
     * User password reset request event
     */
    const USER_PASSWORD_RESET_REQUEST = 'user_password_reset_request';

    /**
     * User edit role event
     */
    const USER_EDIT_ROLE = 'user_edit_role';
}
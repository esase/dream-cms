<?php

namespace User\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
{
    /**
     * Login event
     */
    const LOGIN = 'login_user';

    /**
     * Login failed event
     */
    const LOGIN_FAILED = 'login_user_failed';

    /**
     * Logout event
     */
    const LOGOUT = 'logout_user';

    /**
     * Get info by xmlrpc event
     */
    const GET_INFO_XMLRPC = 'get_user_info_via_xmlrpc';

    /**
     * Set timezone by xmlrpc event
     */
    const SET_TIMEZONE_XMLRPC = 'set_user_timezone_via_xmlrpc';

    /**
     * Disapprove event
     */
    const DISAPPROVE = 'disapprove_user';

    /**
     * Approve event
     */
    const APPROVE = 'approve_user';

    /**
     * Delete event
     */
    const DELETE = 'delete_user';

    /**
     * Add event
     */
    const ADD = 'add_user';

    /**
     * Edit event
     */
    const EDIT = 'edit_user';

    /**
     * Reset password event
     */
    const RESET_PASSWORD = 'reset_user_password';

    /**
     * Reset password request event
     */
    const RESET_PASSWORD_REQUEST = 'reset_user_password_request';

    /**
     * Edit role event
     */
    const EDIT_ROLE = 'edit_user_role';
}
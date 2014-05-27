<?php

namespace Membership\Event;

use Application\Event\Event as ApplicationEvent;

class Event extends ApplicationEvent
{
    /**
     * Add membership role event
     */
    const ADD_MEMBERSHIP_ROLE = 'add_membership_role';

    /**
     * Edit membership role event
     */
    const EDIT_MEMBERSHIP_ROLE = 'edit_membership_role';

    /**
     * Delete membership role event
     */
    const DELETE_MEMBERSHIP_ROLE = 'delete_membership_role';

    /**
     * Delete membership connection event
     */
    const DELETE_MEMBERSHIP_CONNECTION = 'delete_membership_conection';

    /**
     * Activate membership connection event
     */
    const ACTIVATE_MEMBERSHIP_CONNECTION = 'activate_membership_conection';
}
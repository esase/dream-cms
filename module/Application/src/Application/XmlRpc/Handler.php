<?php

namespace Application\XmlRpc;

use Application\Service\Service as ApplicationService;
use User\Service\Service as UserService;
use XmlRpc\Exception\XmlRpcActionDenied;
use Application\Event\Event as ApplicationEvent;

class Handler extends AbstractHandler
{
    /**
     * Get list of localizations
     *
     * @return array
     */
    public function getLocalizations()
    {
        // check user permission
        if (!UserService::checkPermission('xmlrpc_get_localizations')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // fire event
        $eventDesc = UserService::isGuest()
            ? 'Event - Localizations were got by guest via XmlRpc'
            : 'Event - Localizations were got by user via XmlRpc';

        $eventDescParams = UserService::isGuest()
            ? array()
            : array($this->userIdentity->nick_name);

        ApplicationEvent::fireEvent(ApplicationEvent::GET_LOCALIZATIONS,
                0, $this->userIdentity->user_id, $eventDesc, $eventDescParams);

        return ApplicationService::getLocalizations();
    }
}
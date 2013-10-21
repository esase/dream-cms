<?php

namespace Application\XmlRpc;

use Application\Service\Service as ApplicationService;
use Users\Service\Service as UsersService;
use XmlRpc\Exception\XmlRpcActionDenied;
use Application\Event\Event as ApplicationEvent;
use Application\Model\Acl as AclModel;

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
        if (!UsersService::checkPermission('application xmlrpc get localizations')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // fire event
        $eventDesc = $this->userIdentity->user_id == AclModel::DEFAULT_GUEST_ID
            ? 'Application get localizations (guest) via XmlRpc'
            : 'Application get localizations via XmlRpc';

        $eventDescParams = $this->userIdentity->user_id == AclModel::DEFAULT_GUEST_ID
            ? array()
            : array($this->userIdentity->nick_name);

        ApplicationEvent::fireEvent(ApplicationEvent::
                APPLICATION_GET_LOCALIZATIONS, 0, $this->userIdentity->user_id, $eventDesc, $eventDescParams);

        return ApplicationService::getLocalizations();
    }
}
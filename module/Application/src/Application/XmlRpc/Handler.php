<?php
namespace Application\XmlRpc;

use XmlRpc\Handler\AbstractHandler;
use XmlRpc\Exception\XmlRpcActionDenied;

use Application\Service\Service as ApplicationService;
use User\Service\Service as UserService;
use Application\Event\Event as ApplicationEvent;

class Handler extends AbstractHandler
{
    /**
     * Get list of localizations
     *
     * @throws XmlRpc\Exception\XmlRpcActionDenied
     * @return array
     */
    public function getLocalizations()
    {
        // check user permission
        if (!UserService::checkPermission('xmlrpc_get_localizations')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // fire the get localizations via XmlRpc event
        ApplicationEvent::fireGetLocalizationsViaXmlRpcEvent();

        return ApplicationService::getLocalizations();
    }
}
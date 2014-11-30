<?php
namespace Localization\XmlRpc;

use Acl\Service\Acl as AclService;
use Localization\Event\LocalizationEvent;
use Localization\Service\Localization as LocalizationService;
use XmlRpc\Handler\XmlRpcAbstractHandler;
use XmlRpc\Exception\XmlRpcActionDenied;

class LocalizationHandler extends XmlRpcAbstractHandler
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
        if (!AclService::checkPermission('xmlrpc_get_localizations')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // fire the get localizations via XmlRpc event
        LocalizationEvent::fireGetLocalizationsViaXmlRpcEvent();

        return LocalizationService::getLocalizations();
    }
}
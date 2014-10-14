<?php
namespace XmlRpc\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use XmlRpc\Model\XmlRpcBase as XmlRpcBaseModel;

class XmlRpcCache
{
    /**
     * Clear XmlRpc cache
     *
     * @return boolean
     */
    public static function clearXmlRpcCache()
    {
        return ServiceLocatorService::getServiceLocator()
                ->get('Application\Cache\Static')->clearByTags([XmlRpcBaseModel::CACHE_XMLRPC_DATA_TAG]);
    }
}
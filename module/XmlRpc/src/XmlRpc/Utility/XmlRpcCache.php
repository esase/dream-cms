<?php
namespace XmlRpc\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
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
        return ServiceManagerService::getServiceManager()
                ->get('Application\Cache\Static')->clearByTags([XmlRpcBaseModel::CACHE_XMLRPC_DATA_TAG]);
    }
}
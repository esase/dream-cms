<?php

namespace XmlRpc\Utility;

use Application\Service\ServiceManager;
use XmlRpc\Model\Base as XmlRpcBaseModel;

class Cache
{
    /**
     * Clear XmlRpc cache
     *
     * @return boolean
     */
    public static function clearXmlRpcCache()
    {
        return ServiceManager::getServiceManager()
                ->get('Application\Cache\Static')->clearByTags([XmlRpcBaseModel::CACHE_XMLRPC_DATA_TAG]);
    }
}
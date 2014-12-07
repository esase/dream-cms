<?php
namespace XmlRpc\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use XmlRpc\Model\XmlRpcBase as XmlRpcBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class XmlRpcCache
{
    /**
     * Clear XmlRpc cache
     *
     * @return boolean
     */
    public static function clearXmlRpcCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()
                    ->get('Application\Cache\Static')->clearByTags([XmlRpcBaseModel::CACHE_XMLRPC_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }
}
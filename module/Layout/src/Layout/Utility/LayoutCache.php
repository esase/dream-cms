<?php
namespace Layout\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Layout\Model\LayoutBase as LayoutBaseModel;

class LayoutCache
{
    /**
     * Clear layout cache
     *
     * @return boolean
     */
    public static function clearLayoutCache()
    {
        return ServiceManagerService::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([LayoutBaseModel::CACHE_LAYOUTS_DATA_TAG]);
    }
}
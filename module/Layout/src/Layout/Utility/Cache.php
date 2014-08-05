<?php
namespace Layout\Utility;

use Application\Service\ServiceManager;
use Layout\Model\Base as LayoutBaseModel;

class Cache
{
    /**
     * Clear layout cache
     *
     * @return boolean
     */
    public static function clearLayoutCache()
    {
        return ServiceManager::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([LayoutBaseModel::CACHE_LAYOUTS_DATA_TAG]);
    }
}
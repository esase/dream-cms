<?php

namespace Page\Utility;

use Application\Service\ServiceManager;
use Page\Model\Base as PageBaseModel;

class Cache
{
    /**
     * Clear page cache
     *
     * @return boolean
     */
    public static function clearPageCache()
    {
        return ServiceManager::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([PageBaseModel::CACHE_PAGES_DATA_TAG]);
    }
}
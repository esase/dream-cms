<?php

namespace User\Utility;

use Application\Service\ServiceManager;
use User\Model\Base as UserBaseModel;

class Cache
{
    /**
     * Clear user cache
     *
     * @return boolean
     */
    public static function clearUserCache()
    {
        return ServiceManager::getServiceManager()
                ->get('Application\Cache\Static')->clearByTags([UserBaseModel::CACHE_USER_DATA_TAG]);
    }
}
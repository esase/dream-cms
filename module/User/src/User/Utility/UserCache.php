<?php
namespace User\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use User\Model\UserBase as UserBaseModel;

class UserCache
{
    /**
     * Clear user cache
     *
     * @return boolean
     */
    public static function clearUserCache()
    {
        return ServiceManagerService::getServiceManager()
                ->get('Application\Cache\Static')->clearByTags([UserBaseModel::CACHE_USER_DATA_TAG]);
    }
}
<?php
namespace User\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
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
        return ServiceLocatorService::getServiceLocator()
                ->get('Application\Cache\Static')->clearByTags([UserBaseModel::CACHE_USER_DATA_TAG]);
    }
}
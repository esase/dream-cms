<?php
namespace User\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use User\Model\UserBase as UserBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class UserCache
{
    /**
     * Clear user cache
     *
     * @return boolean
     */
    public static function clearUserCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()
                    ->get('Application\Cache\Static')->clearByTags([UserBaseModel::CACHE_USER_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }
}
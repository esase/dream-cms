<?php
namespace Layout\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Layout\Model\LayoutBase as LayoutBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class LayoutCache
{
    /**
     * Clear layout cache
     *
     * @return boolean
     */
    public static function clearLayoutCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                    get('Application\Cache\Static')->clearByTags([LayoutBaseModel::CACHE_LAYOUTS_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }
}
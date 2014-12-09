<?php
namespace Page\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\Model\PageBase as PageBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class PageCache
{
    /**
     * Clear all page cache
     *
     * @return boolean
     */
    public static function clearPageCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                    get('Application\Cache\Static')->clearByTags([PageBaseModel::CACHE_PAGES_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }
}
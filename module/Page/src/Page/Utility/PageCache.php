<?php
namespace Page\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\Model\PageBase as PageBaseModel;

class PageCache
{
    /**
     * Clear all page cache
     *
     * @return boolean
     */
    public static function clearPageCache()
    {
        return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([PageBaseModel::CACHE_PAGES_DATA_TAG]);
    }
}
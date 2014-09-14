<?php
namespace Page\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Page\Model\PageBase as PageBaseModel;

class PageCache
{
    /**
     * Clear page cache
     *
     * @return boolean
     */
    public static function clearPageCache()
    {
        return ServiceManagerService::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([PageBaseModel::CACHE_PAGES_DATA_TAG]);
    }
}
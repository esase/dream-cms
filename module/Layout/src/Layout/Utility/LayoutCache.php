<?php
namespace Layout\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Layout\Model\LayoutBase as LayoutBaseModel;

class LayoutCache
{
    /**
     * Clear layout cache
     *
     * @return boolean
     */
    public static function clearLayoutCache()
    {
        return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([LayoutBaseModel::CACHE_LAYOUTS_DATA_TAG]);
    }
}
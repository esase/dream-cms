<?php
namespace Localization\Utility;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Localization\Model\LocalizationBase as LocalizationBaseModel;

class LocalizationCache
{
    /**
     * Clear localization cache
     *
     * @return boolean
     */
    public static function clearLocalizationCache()
    {
        return ServiceManagerService::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([LocalizationBaseModel::CACHE_LOCALIZATIONS_DATA_TAG]);
    }
}
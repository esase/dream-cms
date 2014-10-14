<?php
namespace Localization\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
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
        return ServiceLocatorService::getServiceLocator()->
                get('Application\Cache\Static')->clearByTags([LocalizationBaseModel::CACHE_LOCALIZATIONS_DATA_TAG]);
    }
}
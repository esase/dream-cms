<?php
namespace Localization\Utility;

use Application\Service\ServiceManager;
use Localization\Model\Base as LocalizationBaseModel;

class Cache
{
    /**
     * Clear localization cache
     *
     * @return boolean
     */
    public static function clearLocalizationCache()
    {
        return ServiceManager::getServiceManager()->
                get('Application\Cache\Static')->clearByTags([LocalizationBaseModel::CACHE_LOCALIZATIONS_DATA_TAG]);
    }
}
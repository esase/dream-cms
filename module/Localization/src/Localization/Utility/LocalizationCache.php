<?php
namespace Localization\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Localization\Model\LocalizationBase as LocalizationBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class LocalizationCache
{
    /**
     * Clear localization cache
     *
     * @return boolean
     */
    public static function clearLocalizationCache()
    {
        try {
            return ServiceLocatorService::getServiceLocator()->
                    get('Application\Cache\Static')->clearByTags([LocalizationBaseModel::CACHE_LOCALIZATIONS_DATA_TAG]);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }

        return false;
    }
}
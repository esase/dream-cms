<?php

namespace Application\Model;

use Application\Utility\Cache as CacheUtility;
use Application\Event\Event as ApplicationEvent;
use Application\Service\Service as ApplicationService;
use Application\Utility\FileSystem;

use Application\Model\LayoutBaseModel;
use Application\Model\LocalizationBaseModel;
use Application\Model\SettingBaseModel;
use Application\Model\TimeZoneBaseModel;
use Application\Model\AdminMenu as AdminMenuBaseModel;
use Page\Model\Base as PageBaseModel;
use User\Model\Base as UserBaseModel;
use XmlRpc\Model\Base as XmlRpcBaseModel;

class CacheAdministration extends Base
{
    /**
     * Cache static
     */
    const CACHE_STATIC = 'static';

    /**
     * Cache dynamic
     */
    const CACHE_DYNAMIC = 'dynamic';

    /**
     * Cache config
     */
    const CACHE_CONFIG = 'config';

    /**
     * Cache js
     */
    const CACHE_JS = 'js';

    /**
     * Cache css
     */
    const CACHE_CSS = 'css';

    /**
     * Cache layout
     */
    const CACHE_LAYOUT = 'layout';

    /**
     * Cache localization
     */
    const CACHE_LOCALIZATION = 'localization';

    /**
     * Cache setting
     */
    const CACHE_SETTING = 'setting';

    /**
     * Cache timezone
     */
    const CACHE_TIMEZONE = 'timezone';

    /**
     * Cache page
     */
    const CACHE_PAGE = 'page';

    /**
     * Cache user
     */
    const CACHE_USER = 'user';

    /**
     * Cache xmlrpc
     */
    const CACHE_XMLRPC = 'xmlrpc';

    /**
     * Cache admin menu
     */
    const CACHE_ADMIN_MENU = 'admin_menu';

    /**
     * Clear cache
     * 
     * @param string $cache
     * @return boolean
     */
    public function clearCache($cache)
    {
        $clearResult = false;

        switch ($cache) {
            case self::CACHE_STATIC :
                $clearResult = $this->serviceManager->get('Cache\Static')->flush();
                break;

            case self::CACHE_DYNAMIC :
                $clearResult = $this->serviceManager->get('Cache\Dynamic')->flush();
                break;

            case self::CACHE_CONFIG :
                $clearResult = FileSystem::deleteFiles(ApplicationService::getConfigCachePath());
                break;

            case self::CACHE_JS :
                $clearResult = FileSystem::deleteFiles(ApplicationService::getLayoutBaseModelCachePath('js'));
                break;

            case self::CACHE_CSS :
                $clearResult = FileSystem::deleteFiles(ApplicationService::getLayoutBaseModelCachePath());
                break;

            case self::CACHE_LAYOUT :
            case self::CACHE_LOCALIZATION :
            case self::CACHE_SETTING :
            case self::CACHE_TIMEZONE :
            case self::CACHE_PAGE :
            case self::CACHE_USER :
            case self::CACHE_XMLRPC :
            case self::CACHE_ADMIN_MENU :
                $cacheTags = [
                    self::CACHE_LAYOUT => LayoutBaseModel::CACHE_LAYOUTS_DATA_TAG,
                    self::CACHE_LOCALIZATION => LocalizationBaseModel::CACHE_LOCALIZATIONS_DATA_TAG,
                    self::CACHE_SETTING => SettingBaseModel::CACHE_SETTINGS_DATA_TAG,
                    self::CACHE_TIMEZONE => TimeZoneBaseModel::CACHE_TIME_ZONES_DATA_TAG,
                    self::CACHE_PAGE => PageBaseModel::CACHE_PAGES_DATA_TAG,
                    self::CACHE_USER => UserBaseModel::CACHE_USER_DATA_TAG,
                    self::CACHE_XMLRPC => XmlRpcBaseModel::CACHE_XMLRPC_DATA_TAG,
                    self::CACHE_ADMIN_MENU => AdminMenuBaseModel::CACHE_ADMIN_MENU_DATA_TAG
                ];

                $this->staticCacheInstance->clearByTags([$cacheTags[$cache]]);
                break;
        }

        // fire the clear cache event
        if ($clearResult) {
            ApplicationEvent::fireClearCacheEvent($cache);
        }

        return $clearResult;
    }
}
<?php
namespace Page\Utility;

use Application\Utility\ApplicationCache as CacheUtility;
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

    /**
     * Clear language sensitive page caches
     *
     * @param string $language
     * @param integer $pageId
     * @return boolean
     */
    public static function clearLanguageSensitivePageCaches($language, $pageId = null)
    {
        $result = true;
        $cache = ServiceLocatorService::getServiceLocator()->get('Application\Cache\Static');

        $languageSensitiveCaches = [
            PageBaseModel::CACHE_USER_MENU,
            PageBaseModel::CACHE_FOOTER_MENU,
            PageBaseModel::CACHE_PAGES_TREE,
            PageBaseModel::CACHE_WIDGETS_CONNECTIONS
        ];

        // clear language sensitive caches
        foreach ($languageSensitiveCaches as $cacheName) {
            $cacheName = CacheUtility::getCacheName($cacheName . $language);

            if ($cache->hasItem($cacheName)) {
                if (false === ($result = $cache->removeItem($cacheName))) {
                    return $result;
                }
            }
        }

        // clear pages map
        $cacheName = CacheUtility::getCacheName(PageBaseModel::CACHE_PAGES_MAP);
        if ($cache->hasItem($cacheName)) {
            $result = $cache->removeItem($cacheName);
        }

        if ($result && $pageId) {
            $cacheName = CacheUtility::getCacheName(PageBaseModel::CACHE_WIDGETS_SETTINGS_BY_PAGE . $pageId);

            // clear a page's widgets settings cache
            if ($cache->hasItem($cacheName)) {
                $result = $cache->removeItem($cacheName);
            }
        }

        return $result;
    }
}
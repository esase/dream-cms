<?php

namespace Application\Model;

use Application\Utility\Cache as CacheUtility;
use Application\Event\Event as ApplicationEvent;
use Application\Service\Service as ApplicationService;
use Application\Utility\FileSystem;

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
     * Clear cache
     * 
     * @param string $cache (possible values are: static, dynamic, config, js, css)
     * @return boolean
     */
    public function clearCache($cache)
    {
        switch ($cache) {
            case self::CACHE_STATIC :
                $clearResult = $this->clearStaticCache();
                break;
            case self::CACHE_DYNAMIC :
                $clearResult =  $this->clearDynamicCache();
                break;
            case self::CACHE_CONFIG :
                $clearResult = $this->clearConfigCache();
                break;
            case self::CACHE_JS :
                $clearResult = $this->clearJsCache();
                break;
            case self::CACHE_CSS :
                $clearResult = $this->clearCssCache();
                break;
        }
        
        // fire the clear cache event
        if ($clearResult) {
            ApplicationEvent::fireClearCacheEvent($cache);
        }

        return $clearResult;
    }
    
    /**
     * Clear static cache
     *
     * @return boolean
     */
    protected function clearStaticCache()
    {
        return $this->serviceManager->get('Cache\Static')->flush();
    }

    /**
     * Clear dynamic cache
     *
     * @return boolean
     */
    protected function clearDynamicCache()
    {
        return $this->serviceManager->get('Cache\Dynamic')->flush();
    }

    /**
     * Clear config cache
     *
     * @return boolean
     */
    protected function clearConfigCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getConfigCachePath());
    }

    /**
     * Clear js cache
     *
     * @return boolean
     */
    protected function clearJsCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getLayoutCachePath('js'));
    }

    /**
     * Clear css cache
     *
     * @return boolean
     */
    protected function clearCssCache()
    {
        return FileSystem::deleteFiles(ApplicationService::getLayoutCachePath());
    }
}
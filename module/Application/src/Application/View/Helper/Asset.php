<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Application\Utility\Cache as CacheUtility;

class Asset extends AbstractHelper
{
    /**
     * Dynamic cache
     * @var object
     */
    protected $dynamicCacheInstance;

    /**
     * Default module
     */
    const DEFAULT_MODULE = 'application';

    /**
     * Resource path
     */
    const CACHE_RESOURCE_PATH = 'Application_Asset_Resource_Path_';

    /**
     * Class constructor
     *
     * @param object $dynamicCacheInstance
     */
    public function __construct(CacheAdapter $dynamicCacheInstance)
    {
        $this->dynamicCacheInstance = $dynamicCacheInstance;
    }

    /**
     * Get resource's url
     *
     * @param string $fileName
     * @param string $type (possible values are: js, css and image)
     * @param string $module
     * @return string|false
     */
    public function __invoke($fileName, $type = 'js', $module = self::DEFAULT_MODULE)
    {
        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_RESOURCE_PATH, array(
            $fileName,
            $type,
            $module
        ));

        if (null === ($resourcePath = $this->dynamicCacheInstance->getItem($cacheName))) {
            $baseResourcePath = ApplicationService::getLayoutPath();

            // get a resource url
            foreach (ApplicationService::getCurrentLayouts() as $layout) {
                $checkResourcePath = $baseResourcePath . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                if (file_exists($checkResourcePath)) {
                    $resourcePath = $this->view->basePath() . '/' .
                            ApplicationService::getLayoutDir() . '/' . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                    // save data in cache
                    $this->dynamicCacheInstance->setItem($cacheName, $resourcePath);
                }
            }
        }

        return $resourcePath ? $resourcePath : false;
    }
}

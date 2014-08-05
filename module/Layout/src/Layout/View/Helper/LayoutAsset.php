<?php
namespace Layout\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Layout\Service\Layout as LayoutService;
use Zend\Cache\Storage\StorageInterface;
use Application\Utility\Cache as CacheUtility;

class LayoutAsset extends AbstractHelper
{
    /**
     * Cache
     * @var object
     */
    protected $dynamicCacheInstance;

    /**
     * Default module
     */
    const DEFAULT_MODULE = 'application';

    /**
     * Cache resource path
     */
    const CACHE_RESOURCE_PATH = 'Application_Asset_Resource_Path_';

    /**
     * Class constructor
     *
     * @param object $dynamicCacheInstance
     */
    public function __construct(StorageInterface $dynamicCacheInstance)
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
        // generate a dynamicCacheInstance name
        $dynamicCacheInstanceName = CacheUtility::getCacheName(self::CACHE_RESOURCE_PATH, [
            $fileName,
            $type,
            $module
        ]);

        if (null === ($resourcePath = $this->dynamicCacheInstance->getItem($dynamicCacheInstanceName))) {
            $baseResourcePath = LayoutService::getLayoutPath();

            // get a resource url
            foreach (LayoutService::getCurrentLayouts() as $layout) {
                $checkResourcePath = $baseResourcePath . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                if (file_exists($checkResourcePath)) {
                    $resourcePath = $this->view->basePath() . '/' .
                            LayoutService::getLayoutDir() . '/' . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                    // save data in dynamicCacheInstance
                    $this->dynamicCacheInstance->setItem($dynamicCacheInstanceName, $resourcePath);
                }
            }
        }

        return $resourcePath ? $resourcePath : false;
    }
}
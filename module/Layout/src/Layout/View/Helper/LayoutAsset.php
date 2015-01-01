<?php
namespace Layout\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Cache\Storage\StorageInterface;
use Application\Utility\ApplicationCache as CacheUtility;

class LayoutAsset extends AbstractHelper
{
    /**
     * Cache
     * @var object
     */
    protected $dynamicCacheInstance;

    /**
     * Layout path
     * @var string
     */
    protected $layoutPath;

    /**
     * Layouts
     * @var array
     */
    protected $layouts;

    /**
     * Layout dir
     * @var string
     */
    protected $layoutDir;

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
    public function __construct(StorageInterface $dynamicCacheInstance, $layoutPath, array $layouts, $layoutDir)
    {
        $this->dynamicCacheInstance = $dynamicCacheInstance;
        $this->layoutPath = $layoutPath;
        $this->layouts = $layouts;
        $this->layoutDir = $layoutDir;
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
            $baseResourcePath = $this->layoutPath ;

            // get a resource url
            foreach ($this->layouts as $layout) {
                $checkResourcePath = $baseResourcePath . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                if (file_exists($checkResourcePath)) {
                    $resourcePath = $this->layoutDir . '/' . $layout['name'] . '/' . $module . '/' . $type . '/' . $fileName;

                    // save data in dynamicCacheInstance
                    $this->dynamicCacheInstance->setItem($dynamicCacheInstanceName, $resourcePath);
                }
            }
        }

        return $resourcePath ? $resourcePath : false;
    }
}
<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Layout\View\Helper;

use Application\Utility\ApplicationCache as CacheUtility;
use Layout\Service\Layout as LayoutService;
use Zend\View\Helper\AbstractHelper;
use Zend\Cache\Storage\StorageInterface;

class LayoutAsset extends AbstractHelper
{
    /**
     * Default module
     */
    const DEFAULT_MODULE = 'application';

    /**
     * Cache resource path
     */
    const CACHE_RESOURCE_PATH = 'Application_Asset_Resource_Path_';

    /**
     * Cache
     *
     * @var \Zend\Cache\Storage\StorageInterface
     */
    protected $dynamicCacheInstance;

    /**
     * Layout path
     *
     * @var string
     */
    protected $layoutPath;

    /**
     * Layouts
     *
     * @var array
     */
    protected $layouts;

    /**
     * Layout dir
     *
     * @var string
     */
    protected $layoutDir;

    /**
     * Current layout id
     *
     * var integer
     */
    protected static $currentLayoutId;

    /**
     * Class constructor
     *
     * @param \Zend\Cache\Storage\StorageInterface $dynamicCacheInstance
     * @param string $layoutPath
     * @param array $layouts
     * @param string $layoutDir
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
        if (!self::$currentLayoutId) {
            $activeLayouts = LayoutService::getCurrentLayouts();
            self::$currentLayoutId = end($activeLayouts)['name'];
        }

        // generate a dynamicCacheInstance name
        $dynamicCacheInstanceName = CacheUtility::getCacheName(self::CACHE_RESOURCE_PATH, [
            $fileName,
            $type,
            $module,
            self::$currentLayoutId
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
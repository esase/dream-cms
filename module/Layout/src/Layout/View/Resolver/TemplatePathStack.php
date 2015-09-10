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
namespace Layout\View\Resolver;

use Application\Utility\ApplicationCache as CacheUtility;
use Layout\Service\Layout as LayoutService;
use Zend\View\Resolver\TemplatePathStack as BaseTemplatePathStack;
use Zend\View\Renderer\RendererInterface as Renderer;
use Zend\Cache\Storage\StorageInterface;

class TemplatePathStack extends BaseTemplatePathStack
{
    /**
     * Template path
     */
    const CACHE_TEMPLATE_PATH = 'Application_Template_Path_';

    /**
     * Dynamic cache instance
     *
     * @var \Zend\Cache\Storage\StorageInterface
     */
    protected $dynamicCacheInstance;

    /**
     * Current layout id
     *
     * var integer
     */
    protected static $currentLayoutId;

    /**
     * Constructor
     *
     * @param \Zend\Cache\Storage\StorageInterface $dynamicCache
     */
    public function __construct(StorageInterface $dynamicCache)
    {
        $this->dynamicCacheInstance = $dynamicCache;
        parent::__construct();
    }

    /**
     * Retrieve the filesystem path to a view script
     *
     * @param  string $name
     * @param  null|Renderer $renderer
     * @throws \Zend\View\Exception\DomainException
     * @return string
     */
    public function resolve($name, Renderer $renderer = null)
    {
        if (!self::$currentLayoutId) {
            $activeLayouts = LayoutService::getCurrentLayouts();
            self::$currentLayoutId = end($activeLayouts)['name'];
        }

        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_TEMPLATE_PATH, [
            $name,
            $renderer,
            self::$currentLayoutId
        ]);

        // check data in cache
        if (null === ($templatePath = $this->dynamicCacheInstance->getItem($cacheName))) {
            if (false !== ($templatePath = parent::resolve($name, $renderer))) {
                // save data in cache
                $this->dynamicCacheInstance->setItem($cacheName, $templatePath);
            }
        }

        return $templatePath;
    }
}

<?php

namespace Application\View\Resolver;

use Zend\View\Resolver\TemplatePathStack as BaseTemplatePathStack;
use Zend\View\Renderer\RendererInterface as Renderer;
use Application\Utility\Cache as CacheUtilities;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;

/**
 * Resolves view scripts based on a stack of paths
 */
class TemplatePathStack extends BaseTemplatePathStack
{
    /**
     * Dynamic cache instance
     * @var object
     */
    protected $dynamicCacheInstance;

    /**
     * Template path
     */
    const CACHE_TEMPLATE_PATH = 'Application_Template_Path_';

    /**
     * Constructor
     *
     * @param  object $dynamicCache
     */
    public function __construct(CacheAdapter $dynamicCache)
    {
        $this->dynamicCacheInstance = $dynamicCache;
        parent::__construct();
    }

    /**
     * Retrieve the filesystem path to a view script
     *
     * @param  string $name
     * @param  null|Renderer $renderer
     * @return string
     * @throws Exception\DomainException
     */
    public function resolve($name, Renderer $renderer = null)
    {
        // generate cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_TEMPLATE_PATH, array(
            $name,
            $renderer
        ));

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

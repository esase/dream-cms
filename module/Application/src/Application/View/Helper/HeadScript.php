<?php
 
namespace Application\View\Helper;

use Application\Service\Service as ApplicationService;
use Application\View\Helper\HeadResource as HeadResource;
use StdClass;
use Zend\View\Helper\HeadScript as BaseHeadScript;

class HeadScript extends BaseHeadScript
{
    use HeadResource;

    /**
     * Cache file extension
     * @var string
     */
    protected $cacheFileExtension = '.js';

    /**
     * Retrieve string representation
     *
     * @param  string|int $indent Amount of whitespaces or string to use for indention
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        if ($this->view) {
            $useCdata = $this->view->plugin('doctype')->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }

        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>' : '//-->';

        $this->getContainer()->ksort();

        // process scripts
        $processedItems = array();
        $cacheItems = array();

        foreach ($this as $item) {
            if (!$this->isValid($item)) {
                continue;
            }

            // check cache state
            if (!$this->isCacheEnabled()) {
                $processedItems[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
            }
            else {
                // don't cache the file if we have "cache" flag with false value
                if (isset($item->attributes['cache']) &&
                            false === $item->attributes['cache']) {

                    $processedItems[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
                }
                else {
                    // collect scripts for caching
                    $conditional = isset($item->attributes['conditional'])
                        ? $item->attributes['conditional']
                        : null;
    
                    $cacheItems[$item->type][$conditional][] = $item;
                }
            }
        }

        // process cache items
        if ($cacheItems) {
            $processedItems = array_merge($this->
                    processCacheItems($cacheItems, $indent, $escapeStart, $escapeEnd), $processedItems);
        }

        return implode($this->getSeparator(), $processedItems);
    }

    /**
     * Process cache items
     *
     * @param array $cacheItems
     * @param  string $indent      String to add before the item
     * @param  string $escapeStart Starting sequence
     * @param  string $escapeEnd   Ending sequence
     * @return array
     */
    protected function processCacheItems(array $cacheItems, $indent, $escapeStart, $escapeEnd)
    {
        $items = array();

        // process cache items
        foreach ($cacheItems as $scriptType => $conditions) {
            foreach ($conditions as $condition => $scripts) {
                $scriptsHash = null;

                // get scripts hash
                foreach ($scripts as $script) {
                    if (!empty($script->source)) {
                        $scriptsHash .= $script->source;
                    }
                    else {
                        // check the file sheme
                        $fileInfo = parse_url($script->attributes['src']);

                        // add absolute path to file
                        if (empty($fileInfo['scheme'])) {
                            $script->attributes['src'] = dirname(APPLICATION_ROOT) . $script->attributes['src'];
                        }

                        $scriptsHash .= $script->attributes['src'];
                    }
                }

                // check scripts hash
                $cacheFile = md5($scriptsHash . $condition . $scriptType) . $this->cacheFileExtension;
                $layoutCachePath = ApplicationService::getLayoutCachePath('js');

                // generate new cache file
                if (!file_exists($layoutCachePath . $cacheFile)) {
                    $content = null;
                    foreach ($scripts as $script) {
                        if (!empty($script->source)) {
                            $content .= $script->source . PHP_EOL;
                        }
                        else if (!empty($script->attributes['src'])) {
                            // get file content
                            if (false !== ($result = file_get_contents($script->attributes['src']))) {
                                $content .= $result . PHP_EOL;
                            }
                        }
                    }

                    $cacheFilePath = $layoutCachePath . $cacheFile;

                    // write cache
                    $this->genCacheFile($cacheFilePath, $content);

                    // check cache gzip status
                    if ($this->isCacheGzipEnabled()) {
                        $this->gzipContent($cacheFilePath, $content);
                    }
                }

                $itemInfo = new stdClass();
                $itemInfo->type = $scriptType;
                $itemInfo->attributes['conditional'] = $condition;
                $itemInfo->attributes['src'] = $this->view->
                        basePath() . '/' . ApplicationService::getLayoutCacheDir('js') . '/' . $cacheFile;

                $items[] = $this->itemToString($itemInfo, $indent, $escapeStart, $escapeEnd);
            }
        }

        return $items;
    }

    /**
     * Check cache gzip status
     *
     * @return boolean
     */
    protected function isCacheGzipEnabled()
    {
        return (int) ApplicationService::getSetting('application_js_cache_gzip');
    }

    /**
     * Check cache status
     *
     * @return boolean
     */
    protected function isCacheEnabled()
    {
        return (int) ApplicationService::getSetting('application_js_cache');   
    }
}

<?php
 
namespace Application\View\Helper;

use Application\Service\Service as ApplicationService;
use Application\View\Helper\HeadResources as HeadResources;
use StdClass;

class HeadLink extends \Zend\View\Helper\HeadLink
{
    use HeadResources;

    /**
     * Css file type
     * @var string
     */
    protected $cssFileType = 'text/css';

    /**
     * Css file rel
     * @var string
     */
    protected $cssFileRel = 'stylesheet';

    /**
     * Cache css file extension
     * @var string
     */
    protected $cacheCssFileExtension = '.css';

    /**
     * Css imports reg expression
     * @var string
     */
    protected $cssImportsExpression = '/@import.*?[\'|\"]+(?P<importUrl>[a-zA-Z0-9\:\.\/_-]+)[\'|\"]+.*?;/';

    /**
     * Max css nested files imports
     */
    const MAX_CSS_NESTED_FILES_IMPORTS = 10;

    /**
     * Unification of css imports
     *
     * @param string $content
     * @return string
     */
    protected function unificationCssImports($content)
    {
        return preg_replace_callback($this->cssImportsExpression, function($urlInfo) {
            return '@import url("' . $urlInfo['importUrl'] . '");';
        }, $content);
    }

    /**
     * Render link elements as string
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $processedItems = array();
        $cacheItems = array();

        $this->getContainer()->ksort();

        foreach ($this as $item) {
            // check cache state for collection css files
            if ($this->isCssCacheEnabled() && $item->rel ==
                        $this->cssFileRel && $item->type = $this->cssFileType) {

                // don't cache the file if we have "cache" flag with false value
                if (isset($item->extras['cache']) && false === $item->extras['cache']) {
                    unset($item->extras['cache']);
                    $processedItems[] = $this->itemToString($item);
                }                                
                else {
                    // collect files for caching
                    $conditional = !empty($item->conditionalStylesheet)
                        ? $item->conditionalStylesheet
                        : null;

                    $cacheItems[$item->media][$conditional][] = $item;
                }                
            }
            else {
                $processedItems[] = $this->itemToString($item);
            }
        }

        // process cache files
        if ($cacheItems) {
            $processedItems =
                    array_merge($this->processCssCacheItems($cacheItems), $processedItems);
        }

        return $indent .
                implode($this->escape($this->getSeparator()) . $indent, $processedItems);
    }

    /**
     * Process css cache items
     *
     * @param array $cacheItems
     * @return array
     */
    protected function processCssCacheItems(array $cacheItems)
    {
        $items = array();

        // process cache items
        foreach ($cacheItems as $media => $conditions) {
            foreach ($conditions as $condition => $files) {
                $filesHash = null;

                // get files hash
                foreach ($files as $file) {
                    $filesHash .= $file->href;
                }

                // check files hash
                $cacheFile = md5($filesHash . $condition . $media) . $this->cacheCssFileExtension;
                $layoutCachePath = ApplicationService::getLayoutCachePath();

                // generate new cache file
                if (!file_exists($layoutCachePath . $cacheFile)) {
                    $content = null;

                    // get file content
                    foreach ($files as $file) {
                        // check the file sheme
                        $fileInfo = parse_url($file->href);

                        // add absolute path to file
                        if (empty($fileInfo['scheme'])) {
                            $file->path = dirname(APPLICATION_ROOT) . $file->href;
                        }

                        // get file content
                        if (false !== ($result = $this->processCssContent(
                                (isset($file->path)? $file->path : $file->href), dirname($file->href)))) {

                            $content .= $result;
                        }
                    }

                    $cacheFilePath = $layoutCachePath . $cacheFile;

                    // write cache
                    $this->genCacheFile($cacheFilePath, $content);

                    // check css cache gzip status
                    if ($this->isCssCacheGzipEnabled()) {
                        $this->gzipContent($cacheFilePath, $content);
                    }
                }

                // get new url
                $file->href = $this->view->
                        basePath() . '/' . ApplicationService::getLayoutCacheDir() . '/' . $cacheFile;
                
                $items[] = $this->itemToString($file);

            }
        }

        return $items;
    }

    /**
     * Process css content
     *
     * @param string $filePath
     * @param string $baseFileUrl
     * @param integer $nestedLevel
     * @return string
     */
    protected function processCssContent($filePath, $baseFileUrl, $nestedLevel = 0)
    {
        if ($nestedLevel > self::MAX_CSS_NESTED_FILES_IMPORTS) {
            return;
        }

        // load file
        if (null != ($content = file_get_contents($filePath))) {
            // make unification of css imports
            $content = $this->unificationCssImports($content);
           
            // replace relative urls to absolute
            $content = $this->replaceCssRelUrlsToAbs($content, $baseFileUrl);

            // check the file sheme
            $fileInfo = parse_url($filePath);

            // find all css imports
            $content = $this->findCssImports($content,
                    $nestedLevel, (!empty($fileInfo['scheme']) ? dirname($filePath) : null));
        }

        return $content;
    }

    /**
     * Find css imports and include files
     *
     * @param string $content
     * @param integer $nestedLevel
     * @return string
     */
    protected function findCssImports($content, $nestedLevel)
    {
        $content = preg_replace_callback($this->cssImportsExpression, function($urlInfo) use ($nestedLevel) {
            $urlDevider = $urlInfo['importUrl']{0} != '/' ? '/' : null;

            // check the file sheme
           $fileInfo = parse_url($urlInfo['importUrl']);

           $filePath = empty($fileInfo['scheme'])
                ? dirname(APPLICATION_ROOT) . $urlDevider . $urlInfo['importUrl']
                : $urlInfo['importUrl'];

            // get file
            return  $this->processCssContent($filePath, dirname($urlInfo['importUrl']), $nestedLevel + 1);

        }, $content);

        return $content;
    }

    /**
     * Replace css relative urls to absolute
     *
     * @param string $content
     * @param string $baseFileUrl
     * @return string
     */
    protected function replaceCssRelUrlsToAbs($content, $baseFileUrl)
    {
        $searchPattern = '/url\s*[\(]*\s*[\'|\"]+(?P<url>[a-zA-Z0-9\.\/\?\#_-]+)[\'|\"]+[\)]*/';

        return preg_replace_callback($searchPattern, function($urlInfo) use ($baseFileUrl) {
            return 'url("' . $baseFileUrl . ($urlInfo['url']{0} != '/' ? '/' : null) . $urlInfo['url'] . '")';
        }, $content);
    }

    /**
     * Check css cache gzip status
     *
     * @return boolean
     */
    protected function isCssCacheGzipEnabled()
    {
        return (int) ApplicationService::getSetting('application_css_cache_gzip');
    }

    /**
     * Check css cache status
     *
     * @return boolean
     */
    protected function isCssCacheEnabled()
    {
        return (int) ApplicationService::getSetting('application_css_cache');
    }
}

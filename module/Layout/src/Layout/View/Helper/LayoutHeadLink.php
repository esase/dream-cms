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

use Layout\Service\Layout as LayoutService;
use Application\Service\ApplicationSetting as SettingService;
use Zend\View\Helper\HeadLink as BaseHeadLink;

class LayoutHeadLink extends BaseHeadLink
{
    use LayoutHeadResource;

    /**
     * Max css nested files imports
     */
    const MAX_CSS_NESTED_FILES_IMPORTS = 10;

    /**
     * Css file type
     *
     * @var string
     */
    protected $cssFileType = 'text/css';

    /**
     * Css file rel
     *
     * @var string
     */
    protected $cssFileRel = 'stylesheet';

    /**
     * Cache css file extension
     *
     * @var string
     */
    protected $cacheCssFileExtension = '.css';

    /**
     * Css imports reg expression
     *
     * @var string
     */
    protected $cssImportsExpression = '/@import.*?[\'|\"]+(?P<importUrl>[a-zA-Z0-9\:\.\/_-]+)[\'|\"]+.*?;/';

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
     * @param  string|integer $indent
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $processedItems = [];
        $cacheItems     = [];

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
        $items = [];

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
                $layoutCachePath = LayoutService::getLayoutCachePath();

                // generate new cache file
                if (!file_exists($layoutCachePath . $cacheFile)) {
                    $content = null;

                    // get file content
                    foreach ($files as $file) {
                        // check the file scheme
                        $fileInfo = parse_url($file->href);

                        // add absolute path to file
                        if (empty($fileInfo['scheme'])) {
                            $file->path = APPLICATION_PUBLIC . '/' . $file->href;
                        }

                        $basePath = empty($fileInfo['scheme'])
                            ? $this->view->basePath() . '/' . dirname($file->href)
                            : dirname($file->href);

                        // get file content
                        if (false !== ($result = $this->
                                processCssContent((isset($file->path) ? $file->path : $file->href), $basePath))) {

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
                $file->href = LayoutService::getLayoutCacheDir() . '/' . $cacheFile;
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

            // find all css imports
            $content = $this->findCssImports($content, $nestedLevel);
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
            $urlDivider = $urlInfo['importUrl']{0} != '/' ? '/' : null;

            $basePath = $this->view->basePath();

            // check the file scheme
            $fileInfo = parse_url($urlInfo['importUrl']);

            // check for base path
            if (empty($fileInfo['scheme'])
                    && substr($urlInfo['importUrl'], 0, strlen($basePath)) == $basePath) {

                $urlInfo['importUrl'] = substr($urlInfo['importUrl'], strlen($basePath));
            }

            $filePath = empty($fileInfo['scheme'])
                ? APPLICATION_PUBLIC . $urlDivider . $urlInfo['importUrl']
                : $urlInfo['importUrl'];

            $basePath = empty($fileInfo['scheme'])
                ? $basePath . dirname($urlInfo['importUrl'])
                : dirname($urlInfo['importUrl']);

            // get file
            return  $this->processCssContent($filePath, $basePath, $nestedLevel + 1);
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
        return (int) SettingService::getSetting('application_css_cache_gzip');
    }

    /**
     * Check css cache status
     *
     * @return boolean
     */
    protected function isCssCacheEnabled()
    {
        return (int) SettingService::getSetting('application_css_cache');
    }
}

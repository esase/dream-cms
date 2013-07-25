<?php

namespace Custom\Cache;

class Utils implements UtilsInterface
{
    /**
     * Cache instance
     */
    protected $cache;

    /**
     * Class constructor
     *
     * @param object $cache
     */
    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get cache instance
     *
     * @return object
     */
    public function getCacheInstance()
    {
       return $this->cache;
    }

    /**
     * Get cache name
     * 
     * @param $methodName
     * @param array $argsList
     * @param boolean $hashed
     * @return string
     */
    public function getCacheName($methodName, $argsList = array(), $hashed = true)
    {
        $cacheName = $methodName . $this->processArgs($argsList);
        return $hashed ? md5($cacheName) : $cacheName;
    }

    /**
     * Process arguments
     *
     * @param mixed $args
     * @return string
     */
    private function processArgs($args)
    {
        $result = null;

        if(!$args) {
            return;
        }

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $result .= $this->processArgs($arg);
            }
            else if( is_scalar($arg) ) {
                $result .= ':' . $arg;
            }
        }

        return $result;
    }
}

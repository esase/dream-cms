<?php

namespace Custom\Cache;

interface UtilsInterface
{
    /**
     * Get cache instance
     *
     * @return object
     */
    public function getCacheInstance();

    /**
     * Get cache name
     * 
     * @param $methodName
     * @param array $argsList
     * @param boolean $hashed
     * @return string
     */
    public function getCacheName($methodName, $argsList = array(), $hashed = true);
}

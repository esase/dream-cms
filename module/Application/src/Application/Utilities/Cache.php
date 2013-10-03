<?php

namespace Application\Utilities;

class Cache
{
    /**
     * Get cache name
     * 
     * @param $methodName
     * @param array $argsList
     * @return string
     */
    public function getCacheName($methodName, array $argsList = array())
    {
        return md5($methodName . self::processArgs($argsList));
    }

    /**
     * Process arguments
     *
     * @param mixed $args
     * @return string
     */
    private static function processArgs($args)
    {
        $result = null;

        if(!$args) {
            return;
        }

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $result .= self::processArgs($arg);
            }
            else if( is_scalar($arg) ) {
                $result .= ':' . $arg;
            }
        }

        return $result;
    }
}
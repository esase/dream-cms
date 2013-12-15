<?php

require_once 'HTMLPurifier.includes.php';

class HTMLPurifierStandalone
{
    /**
     * Purifier
     * @var object
     */
    protected static $purifier = false;

    /**
    * Purify HTML.
    * @param $html String HTML to purify
    * @param $config Configuration to use, can be any value accepted by
    *        HTMLPurifier_Config::create()
    */
    public static function purify($html, $config = null)
    {
        if (!self::$purifier) {
            self::$purifier = new HTMLPurifier();
        }

        return self::$purifier->purify($html, $config);
    }
}
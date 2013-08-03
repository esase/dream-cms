<?php

namespace Application\Service;

class Service
{
    /**
     * Current site localization
     * @var array
     */
    private static $currentLocalization;

    /**
     * Current site layouts
     * @var array
     */
    private static $currentLayouts;

    /**
     * Set current localization
     *
     * @param array $localization
     * @return void
     */
    public static function setCurrentLocalization(array $localization)
    {
        self::$currentLocalization = $localization;
    }

    /**
     * Get current localization
     *
     * @return array
     */
    public static function getCurrentLocalization()
    {
        return self::$currentLocalization;
    }

    /**
     * Set current layouts
     *
     * @param array $layouts
     * @return void
     */
    public static function setCurrentLayouts(array $layouts)
    {
        self::$currentLayouts = $layouts;
    }

    /**
     * Get current layouts
     *
     * @return array
     */
    public static function getCurrentLayouts()
    {
        return self::$currentLayouts;
    }
}
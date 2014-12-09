<?php
namespace Localization\Service;

class Localization
{
    /**
     * Current site localization
     * @var array
     */
    protected static $currentLocalization;

    /**
     * List of all localizations
     * @var array
     */
    protected static $localizations;

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
     * Set localizations list 
     *
     * @param array $localizations
     * @return void
     */
    public static function setLocalizations(array $localizations)
    {
        self::$localizations = $localizations;
    }

    /**
     * Get localizations
     *
     * @return array
     */
    public static function getLocalizations()
    {
        return self::$localizations;
    }

    /**
     * Get default localization
     *
     * @return array
     */
    public static function getDefaultLocalization()
    {
        return self::$localizations ? current(self::$localizations) : [];
    }
}
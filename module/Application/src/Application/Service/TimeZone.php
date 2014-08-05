<?php
namespace Application\Service;

class TimeZone
{
    /**
     * Time zones
     * @var array
     */
    protected static $timeZones;

    /**
     * Set time zones
     *
     * @param array $timeZones
     * @return void
     */
    public static function setTimeZones(array $timeZones)
    {
        self::$timeZones = $timeZones;
    }

    /**
     * Get time zones
     *
     * @return array
     */
    public static function getTimeZones()
    {
        return self::$timeZones;
    }
}
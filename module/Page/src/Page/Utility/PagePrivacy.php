<?php
namespace Page\Utility;

use Page\PagePrivacy\IPagePrivacy;
use Page\Exception\PageException;

class PagePrivacy
{
    /**
     * Privacy instances
     * @var array
     */
    protected static $privacyInstances = [];

    /**
     * Check page privacy
     *
     * @param string $className
     * @param array $privacyOptions
     * @param boolean $trustedPrivacyData
     * @throws Page\Exception\PageException
     * @return boolean
     */
    public static function checkPagePrivacy($className = null, array $privacyOptions = [], $trustedPrivacyData = false)
    {
        if ($className) {
            if (!array_key_exists($className, self::$privacyInstances)) {
                self::$privacyInstances[$className] = new $className;

                if (!self::$privacyInstances[$className] instanceof IPagePrivacy) {
                    throw new PageException(sprintf($className . ' must be an object implementing IPagePrivacy'));
                }
            }

            if (!self::$privacyInstances[$className]->isAllowedViewPage($privacyOptions, $trustedPrivacyData)) {
                return false;
            }
        }

        return true;
    }
}
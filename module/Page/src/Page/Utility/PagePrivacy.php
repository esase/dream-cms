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
     * @throws Page\Exception\PageException
     * @return boolean
     */
    public static function checkPagePrivacy($className = null)
    {
        if ($className) {
            if (!array_key_exists($className, self::$privacyInstances)) {
                self::$privacyInstances[$className] = new $className;

                if (!self::$privacyInstances[$className] instanceof IPagePrivacy) {
                    throw new PageException(sprintf($className . ' must be an object implementing IPagePrivacy'));
                }
            }

            if (!self::$privacyInstances[$className]->isAllowedViewPage()) {
                return false;
            }
        }

        return true;
    }
}
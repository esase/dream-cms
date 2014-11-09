<?php
namespace Page\Utility;

use Page\PageProvider\IPageProvider;
use Page\Exception\PageException;

class PageProvider
{
    /**
     * Get pages
     *
     * @param string $className
     * @param string $language
     * @throws Page\Exception\PageException
     * @return array
     */
    public static function getPages($className, $language)
    {
        $pagesProvider = new $className;

        if (!$pagesProvider instanceof IPageProvider) {
            throw new PageException(sprintf($className . ' must be an object implementing IPageProvider'));
        }

        return $pagesProvider->getPages($language);
    }
}
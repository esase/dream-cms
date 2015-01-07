<?php
namespace Page\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class Page
{
    /**
     * Page layouts
     * @var array
     */
    protected static $layouts = null;

    /**
     * Get page layouts
     *
     * @return array
     */
    public static function getPageLayouts()
    {
        if (null === self::$layouts) {
            self::$layouts = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase')
                ->getPageLayouts();
        }

        return self::$layouts;
    }
}
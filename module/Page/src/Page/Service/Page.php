<?php
namespace Page\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class Page
{
    /**
     * Page layouts
     * @var array
     */
    protected static $pageLayouts = null;

    /**
     * Widget layouts
     * @var array
     */
    protected static $widgetLayouts = null;

    /**
     * Current page
     * @var array
     */
    protected static $currentPage = [];

    /**
     * Get page layouts
     *
     * @return array
     */
    public static function getPageLayouts()
    {
        if (null === self::$pageLayouts) {
            self::$pageLayouts = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageBase')
                ->getPageLayouts();
        }

        return self::$pageLayouts;
    }

    /**
     * Get widget layouts
     *
     * @return array
     */
    public static function getWidgetLayouts()
    {
        if (null === self::$widgetLayouts) {
            self::$widgetLayouts = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageWidgetSetting')
                ->getWidgetLayouts();
        }

        return self::$widgetLayouts;
    }

    /**
     * Set current page
     *
     * @param array $page
     * @return void
     */
    public function setCurrentPage(array $page)
    {
        self::$currentPage = $page;
    }

    /**
     * Get current page
     *
     * @return array
     */
    public static function getCurrentPage()
    {
        return self::$currentPage;
    }
}
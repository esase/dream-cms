<?php
namespace Page\View\Helper;

use Application\Service\Setting as SettingService;
use Page\Model\Page as PageModel;
use Zend\View\Helper\AbstractHelper;

class PageFooterMenu extends AbstractHelper
{
    /**
     * Footer menu
     * @var array
     */
    protected $footerMenu = [];

    /**
     * Class constructor
     *
     * @param array $footerMenu
     */
    public function __construct(array $footerMenu = [])
    {
        $this->footerMenu = $footerMenu;
    }

    /**
     * Page footer menu
     *
     * @param integer $maxFooterColumns
     * @return string
     */
    public function __invoke($maxFooterColumns)
    {
        $footerMenuMaxRows = (int) SettingService::getSetting('page_footer_menu_max_rows');

        $footerMenuProcessed = $footerMenu = null;
        $headerLink = true;

        $totalIndex = $index  = 0;
        $maxAllowedRows = $maxFooterColumns * $footerMenuMaxRows;

        // process the footer menu
        foreach ($this->footerMenu as $menu) {
            // get a page url
            if (false !== ($pageUrl = $this->getView()->pageUrl($menu['slug']))) {
                // get the page's title
                $pageTitle = PageModel::PAGE_TYPE_SYSTEM == $menu['type']
                        ? $this->getView()->translate($menu['system_title']) 
                        : $this->getView()->escapeHtml($menu['title']);

                if ($headerLink) {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-header', [
                        'url' => $pageUrl,
                        'title' => $pageTitle
                    ]);

                    $headerLink = false;
                }
                else {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-item', [
                        'url' => $pageUrl,
                        'title' => $pageTitle
                    ]);
                }

                $index++;
                $totalIndex++;

                // check end of a column
                if ($index == $footerMenuMaxRows) {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-end');

                    $headerLink = true;
                    $index = 0;
                }

                // wrap content
                if ($totalIndex == $maxAllowedRows) {
                    $footerMenuProcessed .= $this->getView()->partial('page/partial/footer-menu', [
                        'footer_menu' => $footerMenu
                    ]);

                    $footerMenu = null;
                    $headerLink = true;
                    $index = $totalIndex = 0;
                }
            }
        }

        // generate end of the footer menu
        if ($index) {
            $footerMenu .= $this->getView()->partial('page/partial/footer-menu-end');
        }

        // wrap content
        if ($footerMenu) {
            $footerMenu =  $this->getView()->partial('page/partial/footer-menu', [
                'footer_menu' => $footerMenu
            ]);
        }

        return $footerMenuProcessed . $footerMenu;
    }
}
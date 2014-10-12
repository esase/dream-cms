<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageUserMenu extends AbstractHelper
{
    /**
     * User menu
     * @var array
     */
    protected $userMenu = [];

    /**
     * Processed user menu
     * @var array
     */
    protected $processedUserMenu = null;

    /**
     * Class constructor
     *
     * @param array $userMenu
     */
    public function __construct(array $userMenu = [])
    {
        $this->userMenu = $userMenu;
    }

    /**
     * Page user menu
     *
     * @return array
     */
    public function __invoke()
    {
        if (null === $this->processedUserMenu) {
            $this->processedUserMenu = [];

            foreach ($this->userMenu as $menu) {
                // get a page url
                if (false !== ($pageUrl = $this->getView()->pageUrl($menu['slug']))) {
                    $this->processedUserMenu[] = [
                        'url' => $pageUrl,
                        'title' => $this->getView()->pageTitle($menu)
                    ];
                }
            }
        }

        return $this->processedUserMenu;
    }
}
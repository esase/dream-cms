<?php

namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Users\Service\Service as UsersService;

class AdminMenu extends AbstractHelper
{
    /**
     * Admin menu
     * @var array
     */
    protected $menu;

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $menu = array())
    {
        if ($menu) {
            foreach ($menu as $menuItem) {
                // check user permission
                if (!UsersService::checkPermission($menuItem['controller'] . '_' . $menuItem['action'], false)) {
                    continue;
                }

                $this->menu[] = $menuItem;
            }
        }
    }

    /**
     * Admin menu
     *
     * @return object - fluent interface
     */
    public function __invoke()
    {
        return $this->menu;
    }
}

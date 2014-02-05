<?php

namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

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
                if (!UserService::checkPermission($menuItem['controller'] . ' ' . $menuItem['action'], false)) {
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

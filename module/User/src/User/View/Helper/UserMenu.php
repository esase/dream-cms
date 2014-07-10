<?php

namespace User\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

class UserMenu extends AbstractHelper
{
    /**
     * User menu
     * @var array
     */
    protected $menu = array();

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $menu = array())
    {
        foreach ($menu as $menuItem) {
            if (!empty($menuItem['check']) && false === eval($menuItem['check'])) {
                continue;
            }

            $this->menu[] = $menuItem;
        }
    }

    /**
     * User menu
     *
     * @return array
     */
    public function __invoke()
    {
        return $this->menu;
    }
}
<?php
namespace User\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class UserMenu extends AbstractHelper
{
    /**
     * User menu
     * @var array
     */
    protected $menu = [];

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $menu = [])
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
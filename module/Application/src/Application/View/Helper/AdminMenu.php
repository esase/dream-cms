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
    protected $menu = array();

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $menu = array())
    {
        if ($menu) {
            // check menu permissions
            foreach ($menu as $menuPart => $menuInfo) {
                foreach ($menuInfo['items'] as $menuItem) {
                    // check a permission
                    if (!UserService::checkPermission($menuItem['controller'] . ' ' . $menuItem['action'], false)) {
                        continue;
                    }

                    if (!isset($this->menu[$menuPart])) {
                        $this->menu[$menuPart] = array(
                            'part' => $menuInfo['part'],
                            'icon' => $menuInfo['icon'],
                            'module' => $menuInfo['module'],
                            'items' => array(
                                0 => array(
                                    'name' => $menuItem['name'],
                                    'controller' => $menuItem['controller'],
                                    'action'  => $menuItem['action'],
                                    'category' => $menuItem['category'],
                                    'category_icon' => $menuItem['category_icon'],
                                    'category_module' => $menuItem['category_module']
                                )
                            )
                        );
                    }
                    else {
                        $this->menu[$menuPart]['items'][] = array(
                            'name' => $menuItem['name'],
                            'controller' => $menuItem['controller'],
                            'action'  => $menuItem['action'],
                            'category' => $menuItem['category'],
                            'category_icon' => $menuItem['category_icon'],
                            'category_module' => $menuItem['category_module']
                        );
                    }
                }
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

<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Acl\Service\Acl as AclService;

class ApplicationAdminMenu extends AbstractHelper
{
    /**
     * Admin menu
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
        if ($menu) {
            // check menu permissions
            foreach ($menu as $menuPart => $menuInfo) {
                foreach ($menuInfo['items'] as $menuItem) {
                    // check a permission
                    if (!AclService::checkPermission($menuItem['controller'] . ' ' . $menuItem['action'], false)) {
                        continue;
                    }

                    if (!isset($this->menu[$menuPart])) {
                        $this->menu[$menuPart] = [
                            'part' => $menuInfo['part'],
                            'icon' => $menuInfo['icon'],
                            'module' => $menuInfo['module'],
                            'items' => [
                                0 => [
                                    'name' => $menuItem['name'],
                                    'controller' => $menuItem['controller'],
                                    'action'  => $menuItem['action'],
                                    'category' => $menuItem['category'],
                                    'category_icon' => $menuItem['category_icon'],
                                    'category_module' => $menuItem['category_module']
                                ]
                            ]
                        ];
                    }
                    else {
                        $this->menu[$menuPart]['items'][] = [
                            'name' => $menuItem['name'],
                            'controller' => $menuItem['controller'],
                            'action'  => $menuItem['action'],
                            'category' => $menuItem['category'],
                            'category_icon' => $menuItem['category_icon'],
                            'category_module' => $menuItem['category_module']
                        ];
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

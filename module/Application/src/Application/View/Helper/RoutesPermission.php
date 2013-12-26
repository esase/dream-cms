<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Users\Service\Service as UsersService;

class RoutesPermission extends AbstractHelper
{
    /**
     * Check routes permission
     *
     * @param array $routes
     *      string controller required
     *      string action required
     *      boolean check_acl optional
     *      string acl_resource optional
     * @param boolean $increaseActions
     * @param boolean $collectDisallowed
     * @return array
     */
    public function __invoke(array $routes, $increaseActions = false, $collectDisallowed = false)
    {
        $processedRoutes = array();

        // process routes
        foreach ($routes as $route) {
            // check a route acl
            if (isset($route['check_acl']) && $route['check_acl'] === true) {
                    $aclResource = !empty($route['acl_resource'])
                        ? $route['acl_resource'] // check permission for specific acl resource
                        : $route['controller'] . ' ' . $route['action']; // check permission for specific controller and action

                // check a permission
                if (!UsersService::checkPermission($aclResource, $increaseActions)) {
                    if (!$collectDisallowed) {
                        continue;
                    }
                    else {
                        $route['permission'] = false;
                    }
                }
            }

            // fill actions
            $processedRoutes[] = $route;
        }

        return $processedRoutes;
    }
}

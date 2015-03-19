<?php
namespace User\PagePrivacy;

use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Acl\Service\Acl as AclService;
use Page\PagePrivacy\PageAbstractPagePrivacy;
use User\Model\UserWidget as UserWidgetModel;

class UserViewPrivacy extends PageAbstractPagePrivacy
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserWidget');
        }

        return $this->model;
    }

    /**
     * Is allowed view page
     * 
     * @param array $privacyOptions
     * @param boolean $trusted
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false)
    {
        // check a permission
        if (!AclService::checkPermission('users_view_profile', false)) {
            return false;
        }

        if (!$trustedData) {
            $userId = !empty($privacyOptions['user_id']) || $this->objectId
                ? (!empty($privacyOptions['user_id']) ? $privacyOptions['user_id'] : $this->objectId) 
                : RouteParamUtility::getParam('slug', -1);
    
            $userField = !empty($privacyOptions['user_id']) 
                ? UserWidgetModel::USER_INFO_BY_ID
                : UserWidgetModel::USER_INFO_BY_SLUG;
    
            // check an existing user
            if (null == ($userInfo = $this->getModel()->getUserInfo($userId, $userField))) {
                return false;
            }
        }

        return true;
    }
}
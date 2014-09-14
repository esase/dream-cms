<?php
namespace User\PagePrivacy;

use Application\Service\ApplicationServiceManager as ServiceManagerService;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Page\PagePrivacy\PageAbstractPagePrivacy;
use User\Model\UserWidget as UserWidgetModel;
use User\Service\UserIdentity as UserIdentityService;

class UserActivatePrivacy extends PageAbstractPagePrivacy
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
            $this->model = ServiceManagerService::getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserWidget');
        }

        return $this->model;
    }

    /**
     * Is allowed to view page
     *
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [])
    {
        $userId = !empty($privacyOptions['user_id']) 
            ? $privacyOptions['user_id'] 
            : RouteParamUtility::getParam('slug', -1);

        $userField = !empty($privacyOptions['user_id']) 
            ? UserWidgetModel::USER_INFO_BY_ID
            : UserWidgetModel::USER_INFO_BY_SLUG;

        if (!UserIdentityService::isGuest() 
                || null == ($userInfo = $this->getModel()->getUserInfo($userId, $userField))) {

            return false;
        }

        // check the user's status
        if ($userInfo['status'] != UserWidgetModel::STATUS_DISAPPROVED) {
            return false;
        }

        return true;
    }
}
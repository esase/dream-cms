<?php
namespace User\PagePrivacy;

use Application\Service\ServiceManager as ServiceManagerService;
use Application\Utility\RouteParam as RouteParamUtility;
use Page\PagePrivacy\AbstractPagePrivacy;
use User\Model\UserWidget as UserWidgetModel;
use User\Service\UserIdentity as UserIdentityService;

class UserActivatePrivacy extends AbstractPagePrivacy
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
     * @return boolean
     */
    public function isAllowedViewPage()
    {
        if (!UserIdentityService::isGuest() || null == ($userInfo = $this->
                getModel()->getUserInfo(RouteParamUtility::getParam('slug', -1), UserWidgetModel::USER_INFO_BY_SLUG))) {

            return false;
        }

        // check the user's status
        if ($userInfo['status'] != UserWidgetModel::STATUS_DISAPPROVED) {
            return false;
        }

        return true;
    }
}
<?php
namespace User\PagePrivacy;

use Application\Utility\RouteParam as RouteParamUtility;
use Application\Service\ServiceManager as ServiceManagerService;
use Page\PagePrivacy\AbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;
use User\Model\UserWidget as UserWidgetModel;

class UserPasswordResetPrivacy extends AbstractPagePrivacy
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

        return true;
    }
}
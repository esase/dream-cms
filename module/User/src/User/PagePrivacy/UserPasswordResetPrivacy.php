<?php
namespace User\PagePrivacy;

use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\PagePrivacy\PageAbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;
use User\Model\UserWidget as UserWidgetModel;

class UserPasswordResetPrivacy extends PageAbstractPagePrivacy
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
     * Is allowed to view page
     *
     * @param array $privacyOptions
     * @param boolean $trustedData
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false)
    {
        if (!UserIdentityService::isGuest()) {
            return false;
        }    

        if (!$trustedData) {
            $userInfo = $this->getModel()->
                    getUserInfo(RouteParamUtility::getParam('slug', -1), UserWidgetModel::USER_INFO_BY_SLUG);

            if (null == $userInfo) {
                return false;
            }
        }

        return true;
    }
}
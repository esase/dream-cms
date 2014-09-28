<?php
namespace User\View\Widget;

use Acl\Service\Acl as AclService;
use User\Model\UserWidget as UserWidgetModel;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;

class UserInfoWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // check a permission
        if (AclService::checkPermission('users_view_profile')) {
            // get the current user's info
            if (null != ($userInfo = 
                        $this->getModel()->getUserInfo($this->getSlug(), UserWidgetModel::USER_INFO_BY_SLUG))) {

                $viewerNickName = !UserIdentityService::isGuest()
                    ? UserIdentityService::getCurrentUserIdentity()['nick_name']
                    : null;

                // fire the get user's info event
                UserEvent::fireGetUserInfoEvent($userInfo['user_id'],
                        $userInfo['nick_name'], UserIdentityService::getCurrentUserIdentity()['user_id'], $viewerNickName);

                // breadcrumb
                $this->getView()->pageBreadcrumb()->setCurrentPageTitle($userInfo['nick_name']);
    
                return $this->getView()->partial('user/widget/info', [
                    'user' => $userInfo
                ]);
            }
        }

        return false;
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->translate('User information');
    }
}
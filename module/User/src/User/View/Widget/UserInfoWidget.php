<?php
namespace User\View\Widget;

use User\Model\UserWidget as UserWidgetModel;

class UserInfoWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get the current user's info
        if (null != ($userInfo = 
                    $this->getModel()->getUserInfo($this->getSlug(), UserWidgetModel::USER_INFO_BY_SLUG))) {

            // breadcrumb
            $this->getView()->pageBreadcrumb()->setCurrentPageTitle($userInfo['nick_name']);

            return $this->getView()->partial('user/widget/info', [
                'user' => $userInfo
            ]);
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
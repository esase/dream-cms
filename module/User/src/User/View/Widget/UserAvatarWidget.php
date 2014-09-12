<?php
namespace User\View\Widget;

use User\Model\UserWidget as UserWidgetModel;

class UserAvatarWidget extends UserAbstractWidget
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

            return $this->getView()->partial('user/widget/avatar', [
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
        return $this->translate('Avatar');
    }
}
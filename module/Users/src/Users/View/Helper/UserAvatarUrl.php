<?php

namespace Users\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;
use Users\Model\Base as UsersModelBase;

class UserAvatarUrl extends AbstractHelper
{
    /**
     * User dummy thumbnail
     */
    const USER_DUMMY_THUMBNAIL = 'user_dummy_thumbnail.png';

    /**
     * User dummy avatar
     */
    const USER_DUMMY_AVATAR  = 'user_dummy_avatar.png';

    /**
     * User avatar url
     *
     * @param sting $avatarName
     * @param boolean $thumbnail
     * @param boolean $getDummy
     * @return string
     */
    public function __invoke($avatarName = null, $thumbnail = true, $getDummy = true)
    {
        if ($avatarName) {
            return $thumbnail
                ? ApplicationService::getResourcesUrl() . UsersModelBase::getThumbnailsDir() . $avatarName
                : ApplicationService::getResourcesUrl() . UsersModelBase::getAvatarsDir()    . $avatarName;
        }
        else if ($getDummy) {
            return $this->getView()->
                    asset(($thumbnail ? self::USER_DUMMY_THUMBNAIL : self::USER_DUMMY_AVATAR), 'images', 'users');
        }
    }
}

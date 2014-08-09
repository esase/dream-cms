<?php
namespace User\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class UserAvatarUrl extends AbstractHelper
{
    /**
     * Thumbnails url
     * @var string
     */
    protected $thumbnailsUrl;

    /**
     * Avatars url
     * @var string
     */
    protected $avatarsUrl;

    /**
     * User dummy thumbnail
     */
    const USER_DUMMY_THUMBNAIL = 'user_dummy_thumbnail.png';

    /**
     * User dummy avatar
     */
    const USER_DUMMY_AVATAR  = 'user_dummy_avatar.png';

    /**
     * Class constructor
     *
     * @param string $thumbnailsUrl
     * @param string $avatarsUrl
     */
    public function __construct($thumbnailsUrl, $avatarsUrl)
    {
        $this->thumbnailsUrl = $thumbnailsUrl;
        $this->avatarsUrl = $avatarsUrl;
    }

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
            return $thumbnail ? $this->thumbnailsUrl . $avatarName : $this->avatarsUrl . $avatarName;
        }

        // get a dummy image
        if ($getDummy) {
            return $this->getView()->layoutAsset(($thumbnail 
                    ? self::USER_DUMMY_THUMBNAIL : self::USER_DUMMY_AVATAR), 'image', 'user');
        }
    }
}
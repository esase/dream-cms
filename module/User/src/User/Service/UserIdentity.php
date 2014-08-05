<?php
namespace User\Service;

use stdClass;
use User\Model\Base as UserBaseModel;

class UserIdentity
{
    /**
     * Current user identity
     * @var object
     */
    protected static $currentUserIdentity;
    
    /**
     * Set current user identity
     *
     * @param object $userIdentity
     * @return void
     */
    public static function setCurrentUserIdentity(stdClass $userIdentity)
    {
        self::$currentUserIdentity = $userIdentity;
    }

    /**
     * Get current user identity
     *
     * @return object
     */
    public static function getCurrentUserIdentity()
    {
        return self::$currentUserIdentity;
    }

    /**
     * Get user info
     *
     * @param integer $userId
     * @param string $field
     * @return array
     */
    public static function getUserInfo($userId, $field = null)
    {
        return self::$serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\Base')
            ->getUserInfo($userId, $field);
    }

    /**
     * Check is admin or not
     *
     * @return boolean
     */
    public static function isAdmin()
    {
        return self::getCurrentUserIdentity()->user_id == AclModelBase::DEFAULT_ROLE_ADMIN;
    }

    /**
     * Check is default user or not
     *
     * @return boolean
     */
    public static function isDefaultUser()
    {
        return self::getCurrentUserIdentity()->user_id == UserBaseModel::DEFAULT_USER_ID;
    }

    /**
     * Check is guest or not
     *
     * @return boolean
     */
    public static function isGuest()
    {
        return self::getCurrentUserIdentity()->user_id == UserBaseModel::DEFAULT_GUEST_ID;
    }
}
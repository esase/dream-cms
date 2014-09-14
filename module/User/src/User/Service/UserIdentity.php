<?php
namespace User\Service;

use User\Model\UserBase as UserBaseModel;

class UserIdentity
{
    /**
     * Current user identity
     * @var array
     */
    protected static $currentUserIdentity;
    
    /**
     * Set current user identity
     *
     * @param array $userIdentity
     * @return void
     */
    public static function setCurrentUserIdentity(array $userIdentity)
    {
        self::$currentUserIdentity = $userIdentity;
    }

    /**
     * Get current user identity
     *
     * @return array
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
            ->getInstance('User\Model\UserBase')
            ->getUserInfo($userId, $field);
    }

    /**
     * Check is default user or not
     *
     * @return boolean
     */
    public static function isDefaultUser()
    {
        return self::getCurrentUserIdentity()['user_id'] == UserBaseModel::DEFAULT_USER_ID;
    }

    /**
     * Check is guest or not
     *
     * @return boolean
     */
    public static function isGuest()
    {
        return self::getCurrentUserIdentity()['user_id'] == UserBaseModel::DEFAULT_GUEST_ID;
    }
}
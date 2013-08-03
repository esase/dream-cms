<?php

namespace Users\Service;

class Service
{
    /**
     * Current user identity
     * @var object
     */
    private static $currentUserIdentity;
    
    /**
     * Current acl
     * @var object
     */
    private static $currentAcl;

    /**
     * Set current user identity
     *
     * @param object $userIdentity
     * @return void
     */
    public static function setCurrentUserIdentity(\stdClass $userIdentity)
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
     * Set current acl
     *
     * @param object $acl
     * @return void
     */
    public static function setCurrentAcl(\Zend\Permissions\Acl\Acl $acl)
    {
        self::$currentAcl = $acl;
    }

    /**
     * Get current acl
     *
     * @return object
     */
    public static function getCurrentAcl()
    {
        return self::$currentAcl;
    }
}
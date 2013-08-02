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
}
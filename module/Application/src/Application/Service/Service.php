<?php

namespace Application\Service;

class Service
{
    /**
     * Current user identity
     * @var object
     */
    protected static $currentUserIdentity;

    /**
     * Current acl
     * @var object
     */
    protected static $currentAcl;

    /**
     * Current acl resources
     * @var object
     */
    protected static $currentAclResources;

    /**
     * Service manager
     */
    protected static $serviceManager;

    /**
     * Current site localization
     * @var array
     */
    protected static $currentLocalization;

    /**
     * Current site layouts
     * @var array
     */
    protected static $currentLayouts;

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
     * Set current acl resources
     *
     * @param array $resources
     * @return void
     */
    public function setCurrentAclResources(array $resources)
    {
        self::$currentAclResources = $resources;    
    }

    /**
     * Get current acl resources
     *
     * @return object
     */
    public static function getCurrentAclResources()
    {
        return self::$currentAclResources;
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

    /**
     * Set service manager
     *
     * @param object $serviceManager
     * @return void
     */
    public static function setServiceManager(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }

    /**
     * Get service manager
     *
     * @return object
     */
    public static function getServiceManager()
    {
        return self::$serviceManager;
    }

    /**
     * Set current localization
     *
     * @param array $localization
     * @return void
     */
    public static function setCurrentLocalization(array $localization)
    {
        self::$currentLocalization = $localization;
    }

    /**
     * Get current localization
     *
     * @return array
     */
    public static function getCurrentLocalization()
    {
        return self::$currentLocalization;
    }

    /**
     * Get setting
     *
     * @param string $settingName
     * @return string|boolean
     */
    public static function getSetting($settingName)
    {
        $settingsModel = self::$serviceManager
           ->get('Application\Model\Builder')
           ->getInstance('Application\Model\Setting');

        return $settingsModel->
                getSetting($settingName, self::$currentLocalization['language']);
    }

    /**
     * Set current layouts
     *
     * @param array $layouts
     * @return void
     */
    public static function setCurrentLayouts(array $layouts)
    {
        self::$currentLayouts = $layouts;
    }

    /**
     * Get current layouts
     *
     * @return array
     */
    public static function getCurrentLayouts()
    {
        return self::$currentLayouts;
    }
}
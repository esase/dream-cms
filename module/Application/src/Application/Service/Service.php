<?php

namespace Application\Service;

use stdClass;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceManager;

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
     * Acl roles
     * @var array
     */
    protected static $aclRoles;

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
     * Time zones
     * @var array
     */
    protected static $timeZones;

    /**
     * List of all localizations
     * @var array
     */
    protected static $localizations;

    /**
     * Get application url
     *
     * @return array
     */
    public static function getApplicationUrl()
    {
        $serviceManager = self::$serviceManager;
        return $serviceManager->get('Request')->getBaseUrl();
    }

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
     * Set current acl resources
     *
     * @param array $resources
     * @return void
     */
    public static function setCurrentAclResources(array $resources)
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
    public static function setCurrentAcl(Acl $acl)
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
     * Get acl roles
     *
     * @param boolean $excludeGuest
     * @return array
     */
    public static function getAclRoles($excludeGuest = true)
    {
        if (!isset(self::$aclRoles[$excludeGuest])) {
            self::$aclRoles[$excludeGuest] = self::$serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\AclAdministration')
                ->getRolesList($excludeGuest);
        }

        return self::$aclRoles[$excludeGuest];
    }

    /**
     * Set service manager
     *
     * @param object $serviceManager
     * @return void
     */
    public static function setServiceManager(ServiceManager $serviceManager)
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
     * Set localizations list 
     *
     * @param array $localizations
     * @return void
     */
    public static function setLocalizations(array $localizations)
    {
        self::$localizations = $localizations;
    }

    /**
     * Get localizations
     *
     * @return array
     */
    public static function getLocalizations()
    {
        return self::$localizations;
    }

    /**
     * Get default localization
     *
     * @return array
     */
    public static function getDefaultLocalization()
    {
        return current(self::$localizations);
    }

    /**
     * Get setting
     *
     * @param string $settingName
     * @param string $language
     * @return string|boolean
     */
    public static function getSetting($settingName, $language = null)
    {
        $settingsModel = self::$serviceManager
           ->get('Application\Model\ModelManager')
           ->getInstance('Application\Model\Setting');

        return $settingsModel->getSetting($settingName,
                ($language ? $language : self::$currentLocalization['language']));
    }

    /**
     * Get config path
     *
     * @return string
     */
    public static function getConfigCachePath()
    {
        return APPLICATION_ROOT .
                '/' . self::$serviceManager->get('Config')['paths']['config_cache'];
    }

    /**
     * Get captcha path
     *
     * @return string
     */
    public static function getCaptchaPath()
    {
        return APPLICATION_PUBLIC .
                '/' . self::$serviceManager->get('Config')['paths']['captcha'] . '/';
    }

    /**
     * Get captcha font path
     *
     * @return string
     */
    public static function getCaptchaFontPath()
    {
        return APPLICATION_PUBLIC . '/' .
                self::$serviceManager->get('Config')['paths']['captcha'] . '/' .
                self::$serviceManager->get('Config')['paths']['captcha_font'];
    }

    /**
     * Get captcha url
     *
     * @return string
     */
    public static function getCaptchaUrl()
    {
        return self::getApplicationUrl() . '/' .
                self::$serviceManager->get('Config')['paths']['captcha'] . '/';
    }

    /**
     * Get layout path
     *
     * @return string
     */
    public static function getLayoutPath()
    {
        return APPLICATION_PUBLIC . '/' .
                self::$serviceManager->get('Config')['paths']['layout'] . '/';
    }

    /**
     * Get resources dir
     *
     * @return string
     */
    public static function getResourcesDir()
    {
        return APPLICATION_PUBLIC . '/' .
                self::$serviceManager->get('Config')['paths']['resource'] . '/';
    }

    /**
     * Get resources url
     *
     * @return string
     */
    public static function getResourcesUrl()
    {
        return self::getApplicationUrl() . '/' .
                self::$serviceManager->get('Config')['paths']['resource'] . '/';
    }

    /**
     * Get layout dir
     *
     * @return string
     */
    public static function getLayoutDir()
    {
        return self::$serviceManager->get('Config')['paths']['layout'];
    }

    /**
     * Get layout cache path
     *
     * @param string $type
     * @return string
     */
    public static function getLayoutCachePath($type = 'css')
    {
        return APPLICATION_PUBLIC . '/' . ($type == 'css'
                ? self::$serviceManager->get('Config')['paths']['layout_cache_css']
                : self::$serviceManager->get('Config')['paths']['layout_cache_js']) . '/';
    }

    /**
     * Get layout cache dir
     *
     * @param string $type
     * @return string
     */
    public static function getLayoutCacheDir($type = 'css')
    {
        return ($type == 'css'
                ? self::$serviceManager->get('Config')['paths']['layout_cache_css']
                : self::$serviceManager->get('Config')['paths']['layout_cache_js']);
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

    /**
     * Set time zones
     *
     * @param array $timeZones
     * @return void
     */
    public static function setTimeZones(array $timeZones)
    {
        self::$timeZones = $timeZones;
    }

    /**
     * Get time zones
     *
     * @return array
     */
    public static function getTimeZones()
    {
        return self::$timeZones;
    }
}
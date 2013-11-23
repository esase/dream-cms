<?php

namespace Application\Service;

class Service
{
    /**
     * Captcha directory name
     * @var string
     */
    protected static $captchaDir = 'captcha';

    /**
     * Captcha font directory name
     * @var string
     */
    protected static $captchaFontDir = 'font/captcha.ttf';

    /**
     * Layouts directory name
     * @var string
     */
    protected static $layoutsDir = 'layouts';

    /**
     * Layouts cache css directory name
     * @var string
     */
    protected static $layoutsCacheCssDir = 'layouts_cache/css';

    /**
     * Layouts cache js directory name
     * @var string
     */
    protected static $layoutsCacheJsDir = 'layouts_cache/js';

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
     * Get captcha path
     *
     * @return string
     */
    public function getCaptchaPath()
    {
        return APPLICATION_PUBLIC . '/' . self::$captchaDir . '/';
    }

    /**
     * Get captcha font path
     *
     * @return string
     */
    public function getCaptchaFontPath()
    {
        return APPLICATION_PUBLIC . '/' . self::$captchaDir . '/' . self::$captchaFontDir;
    }

    /**
     * Get captcha url
     *
     * @return string
     */
    public function getCaptchaUrl()
    {
        return self::getApplicationUrl() . '/' . self::$captchaDir . '/';
    }

    /**
     * Get layout path
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return APPLICATION_PUBLIC . '/' . self::$layoutsDir . '/';
    }

    /**
     * Get layout dir
     *
     * @return string
     */
    public function getLayoutDir()
    {
        return self::$layoutsDir;
    }

    /**
     * Get layout cache path
     *
     * @param string $type
     * @return string
     */
    public function getLayoutCachePath($type = 'css')
    {
        return APPLICATION_PUBLIC . '/' .
                ($type == 'css' ? self::$layoutsCacheCssDir : self::$layoutsCacheJsDir) . '/';
    }

    /**
     * Get layout cache dir
     *
     * @param string $type
     * @return string
     */
    public function getLayoutCacheDir($type = 'css')
    {
        return ($type == 'css' ? self::$layoutsCacheCssDir : self::$layoutsCacheJsDir);
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
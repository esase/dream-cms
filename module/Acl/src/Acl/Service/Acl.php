<?php
namespace Acl\Service;

use Zend\Permissions\Acl\AclInterface;

class Acl
{
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
     * Get acl roles
     *
     * @param boolean $excludeGuest
     * @return array
     */
    public static function getAclRoles($excludeGuest = true)
    {
        if (!isset(self::$aclRoles[$excludeGuest])) {
            self::$aclRoles[$excludeGuest] = ServiceManager::getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('Acl\Model\Base')
                ->getRolesList($excludeGuest);
        }

        return self::$aclRoles[$excludeGuest];
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
    public static function setCurrentAcl(AclInterface $acl)
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
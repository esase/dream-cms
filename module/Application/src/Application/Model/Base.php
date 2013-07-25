<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class Base extends Sql
{
    /**
     * Static cache utils
     * @var object
     */
    protected $staticCacheUtils;

    /**
     * Localization cache
     */
    const CACHE_LOCALIZATIONS = 'Application_Localizations';

    /**
     * Acl roles cache
     */
    const CACHE_ACL_ROLES = 'Application_Acl_Roles';

    /**
     * Class constructor
     *
     * @param object $adapter
     */
    public function __construct(Adapter $adapter, \Custom\Cache\Utils $staticCacheUtils)
    {
        parent::__construct($adapter);
        $this->staticCacheUtils = $staticCacheUtils;
    }
}
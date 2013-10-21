<?php

namespace XmlRpc\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Application\Utility\Cache as CacheUtilities;

class Base extends Sql
{
    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

    /**
     * Cache user by id
     */
    const CACHE_XMLRPC_CLASSES = 'XmlRpc_CLasses';

    /**
     * Class constructor
     *
     * @param object $adapter
     * @param object $staticCacheInstance
     */
    public function __construct(Adapter $adapter, $staticCacheInstance)
    {
        parent::__construct($adapter);
        $this->staticCacheInstance = $staticCacheInstance;
    }
}
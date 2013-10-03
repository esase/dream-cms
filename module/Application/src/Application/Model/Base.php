<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class Base extends Sql
{
    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

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

    /**
     * Get adapter
     *
     * @return object
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
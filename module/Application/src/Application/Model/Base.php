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
     * Class constructor
     *
     * @param object $adapter
     */
    public function __construct(Adapter $adapter, \Custom\Cache\Utils $staticCacheUtils)
    {
        parent::__construct($adapter);
        $this->staticCacheUtils = $staticCacheUtils;
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
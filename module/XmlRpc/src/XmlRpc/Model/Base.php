<?php

namespace XmlRpc\Model;

use Application\Model\AbstractBase;
use Zend\Db\Sql\Sql;

class Base extends AbstractBase
{
    /**
     * Cache user by id
     */
    const CACHE_XMLRPC_CLASSES = 'XmlRpc_CLasses';
}
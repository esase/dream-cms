<?php
namespace XmlRpc\Model;

use Application\Model\AbstractBase;

class Base extends AbstractBase
{
    /**
     * Cache user by id
     */
    const CACHE_XMLRPC_CLASSES = 'XmlRpc_Classes';

    /**
     * XmlRpc data cache tag
     */
    const CACHE_XMLRPC_DATA_TAG = 'XmlRpc_Data_Tag';
}
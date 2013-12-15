<?php

namespace XmlRpc\Exception;
use Exception;

class XmlRpcActionDenied extends Exception
{
    /**
     * Error http code
     * @var integer  
     */
    protected $code = 403;
}
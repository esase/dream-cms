<?php
namespace XmlRpc\Exception;

class XmlRpcActionDenied extends XmlRpcException
{
    /**
     * Error http code
     * @var integer  
     */
    protected $code = 403;
}
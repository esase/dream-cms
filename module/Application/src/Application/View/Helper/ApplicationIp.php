<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class ApplicationIp extends AbstractHelper
{
    /**
     * Get IP
     *
     * @param string $ip
     * @return string
     */
    public function __invoke($ip)
    {
       return inet_ntop($ip);
    }
}
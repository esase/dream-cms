<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Zend\Math\Rand;

class ApplicationRandId extends AbstractHelper
{
    /**
     * Get rand id
     *
     * @param string $prefix
     * @param integer $length
     * @param string $chars
     * @return string
     */
    public function __invoke($prefix = null, $length = 10, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
       return $prefix . strtolower(Rand::getString($length, $chars, true));
    }
}
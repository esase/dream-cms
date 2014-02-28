<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FileSize extends AbstractHelper
{
    /**
     * File size
     *
     * @param integer $bytes
     * @return string
     */
    public function __invoke($bytes)
    {
        if ((int) $bytes) {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes ? $bytes : null;
    }
}

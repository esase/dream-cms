<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Utility\FileSystem as FileSystemUtility;

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
        return FileSystemUtility::convertBytes($bytes);
    }
}

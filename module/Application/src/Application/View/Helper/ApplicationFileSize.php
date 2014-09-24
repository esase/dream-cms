<?php
namespace Application\View\Helper;

use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use Zend\View\Helper\AbstractHelper;

class ApplicationFileSize extends AbstractHelper
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
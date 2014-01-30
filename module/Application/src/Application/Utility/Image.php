<?php

namespace Application\Utility;

use Imagine;
use Application\Service\Service as ApplicationService;

class Image
{
    /**
     * Resize a resource image
     *
     * @param string $file
     * @param string $path
     * @param integer $width
     * @param integer $height
     * @param string $newPath
     * @param boolean $saveProportions
     * @return void
     */
    public static function resizeResourceImage($file, $path, $width, $height, $newPath = null, $saveProportions = true)
    {
        $filePath = ApplicationService::getResourcesDir() . $path . $file;

        // resize the avatar
        $imagine = new Imagine\Gd\Imagine();
        $size    = new Imagine\Image\Box($width, $height);
        $mode    = $saveProportions
            ? Imagine\Image\ImageInterface::THUMBNAIL_INSET
            : Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;

        $imagine->open($filePath)
            ->thumbnail($size, $mode)
            ->save(($newPath ? ApplicationService::getResourcesDir() . $newPath  . $file : $filePath));    
    }
}
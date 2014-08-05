<?php
namespace Layout\View\Helper;

trait LayoutHeadResource
{
    /**
     * Cache gzip file extension
     * @var string
     */
    protected $gzFileExtension = '.gz';

    /**
     * Gzip compression level
     * @var string
     */
    protected $gzCompressionLevel = 'wb9';

    /**
     * Generate cache file
     *
     * @param string $filePath
     * @param string $content
     * @return void
     */
    protected function genCacheFile($filePath, $content)
    {
        file_put_contents($filePath, $content);
    }

    /**
     * Gzip content
     *
     * @param string $filePath
     * @param string $content
     * @return void
     */
    protected function gzipContent($filePath, $content)
    {
        $gzip = gzopen($filePath. $this->gzFileExtension, $this->gzCompressionLevel);
        gzputs($gzip, $content);
        gzclose($gzip);
    }
}
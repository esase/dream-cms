<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;

class Asset extends AbstractHelper
{
    /**
     * Default module
     */
    const DEFAULT_MODULE = 'application';

    /**
     * Get file url
     *
     * @param string $fileName
     * @param string $type (possible values are: js, css and images)
     * @param string $module
     * @return string|false
     */
    public function __invoke($fileName, $type = 'js', $module = self::DEFAULT_MODULE)
    {
        $baseFilePath = ApplicationService::getLayoutPath();

        // get file url
        foreach (ApplicationService::getCurrentLayouts() as $layout) {
            $filePath = $baseFilePath . $layout['name']
                    . '/' . $module . '/' . $type
                    . '/' . $fileName;

            if (file_exists($filePath)) {
                return  $this->view->basePath()
                        . '/' . ApplicationService::getLayoutDir()
                        . '/' . $layout['name'] . '/' . $module
                        . '/' . $type . '/' . $fileName;
            }
        }

        return false;
    }
}

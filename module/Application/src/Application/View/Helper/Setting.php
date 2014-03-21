<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;

class Setting extends AbstractHelper
{
    /**
     * Get setting
     *
     * @param string $setting
     * @param string $language
     * @return string|boolean
     */
    public function __invoke($setting, $language = null)
    {
        return ApplicationService::getSetting($setting, $language);
    }
}

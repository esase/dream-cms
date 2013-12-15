<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;

class Localizations extends AbstractHelper
{
    /**
     * Localizations
     *
     * @return object fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get all localizations
     *
     * @return array
     */
    public function getAllLocalizations()
    {
        return ApplicationService::getLocalizations();
    }

    /**
     * Get current localization
     *
     * @return array
     */
    public function getCurrentLocalization()
    {
        return ApplicationService::getCurrentLocalization();
    }
}

<?php
 
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;

class Localizations extends AbstractHelper
{
    /**
     * Current localization
     * @var array
     */
    protected $currentLocalization;

    /**
     * Localizations
     *
     * @return object fluent interface
     */
    public function __invoke()
    {
        $this->currentLocalization = ApplicationService::getCurrentLocalization();
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
        return $this->currentLocalization;
    }

    /**
     * Is current language LTR
     *
     * @return boolean
     */
    public function isCurrentLanguageLtr()
    {
        return $this->currentLocalization['direction'] == 'ltr';
    }

    /**
     * Get current language
     *
     * @return string
     */
    public function getCurrentLanguage()
    {
        return $this->currentLocalization['language'];
    }

    /**
     * Get current language direction
     *
     * @return string
     */
    public function getCurrentLanguageDirection()
    {
        return $this->currentLocalization['direction'];
    }
}

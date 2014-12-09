<?php
namespace Install\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Localization extends AbstractHelper
{
    /**
     * Current localization
     * @var array
     */
    protected $currentLocalization;

    /**
     * Localizations
     * @var array
     */
    protected $localizations;

    /**
     * Class constructor
     *
     * @param array $currentLocalization
     * @param array $localizations
     */
    public function __construct(array $currentLocalization, array $localizations)
    {
        $this->currentLocalization = $currentLocalization;
        $this->localizations = $localizations;
    }

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
        return $this->localizations;
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
     * Get current language's description
     *
     * @return string
     */
    public function getCurrentLanguageDescription()
    {
        return $this->currentLocalization['description'];
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

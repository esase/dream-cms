<?php
namespace Localization\View\Helper;

use Zend\View\Helper\AbstractHelper;

class LocalizationSwitcher extends AbstractHelper
{
    /**
     * List of languages links
     * @var array
     */
    protected static $languagesLinks = null;

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
    public function __construct(array $localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Language swticher
     *
     * @return array
     */
    public function __invoke()
    {
        if (self::$languagesLinks !== null) {
            return self::$languagesLinks;
        }

        self::$languagesLinks = array();

        if (count($this->localizations) < 2) {
            return self::$languagesLinks;
        }

        // process list of languages
        $languagesLinks = array();
        foreach ($this->localizations as $localization) {
            // collect url params
            $urlParams = array(
                'languge' => $localization['language'], 
                'controller' => $this->getView()->currentRoute()->getController(), 
                'action' => $this->getView()->currentRoute()->getAction());

            $url = $this->getView()->url(null, array_merge($urlParams,  $this->getView()->
                    currentRoute()->getExtraRouteParams()), array('query' => $this->getView()->currentRoute()->getQuery()));

            self::$languagesLinks[] = array(
                'active' => $this->getView()->localization()->getCurrentLanguage() == $localization['language'],
                'language' => $localization['language'],
                'description' => $localization['description'],
                'url' => $url
            );
        }

        return self::$languagesLinks;
    }
}
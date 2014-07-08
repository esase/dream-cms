<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class LanguageSwitcher extends AbstractHelper
{
    /**
     * List of languages links
     * @var array
     */
    protected static $languagesLinks = null;

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
        $languages = $this->getView()->localization()->getAllLocalizations();

        if (count($languages) < 2) {
            return self::$languagesLinks;
        }

        // process list of languages
        $languagesLinks = array();
        foreach ($languages as $localization) {
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
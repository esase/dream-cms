<?php
namespace Page\View\Helper;

use Application\Model\ApplicationAbstractBase as ApplicationAbstractBaseModel;
use Page\Model\PageNestedSet;
use Page\Utility\PagePrivacy as PagePrivacyUtility;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;
use Zend\View\Helper\AbstractHelper;

class PageUrl extends AbstractHelper
{
    /**
     * List of defined urls
     * @var array
     */
    protected $definedUrls = [];

    /**
     * Pages map
     * @var array
     */
    protected $pagesMap = [];

    /**
     * Home page
     * @var string
     */
    protected $homePage;

    /**
     * Class constructor
     *
     * @param array $pagesMap
     * @param string $homePage
     */
    public function __construct(array $pagesMap = [], $homePage)
    {
        $this->pagesMap = $pagesMap;
        $this->homePage = $homePage;
    }

    /**
     * Page url
     *
     * @param string slug
     * @param array $privacyOptions
     * @param string $language
     * @param boolean $trustedPrivacyData
     * @param string $type
     * @return string|boolean
     */
    public function __invoke($slug = null, array $privacyOptions = [], $language = null, $trustedPrivacyData = false, $type = null)
    {
        if (!$slug) {
            $slug = $this->homePage;
        }

        if (!$language) {
            $language = LocalizationService::getCurrentLocalization()['language'];
        }

        if (isset($this->definedUrls[$language]) 
                && array_key_exists($slug, $this->definedUrls[$language])) {

            return $this->definedUrls[$language][$slug];
        }

        $pageUrl = $this->getPageUrl($slug, $privacyOptions, $language, $trustedPrivacyData, $type);

        // compare the slug for the home page 
        if ($this->homePage == $slug && false !== $pageUrl) {
            $pageUrl = null;
        }

        $this->definedUrls[$language][$slug] = $pageUrl;
        return $pageUrl;
    }

    /**
     * Get page url
     *
     * @param string $slug
     * @param array $privacyOptions
     * @param string $language
     * @param boolean $trustedPrivacyData
     * @param string $type
     * @return string|boolean
     */
    protected function getPageUrl($slug, array $privacyOptions = [], $language, $trustedPrivacyData = false, $type = null) 
    {
        if (!isset($this->pagesMap[$language])
                || !array_key_exists($slug, $this->pagesMap[$language])) {

            return false;
        }

        // get a page info
        $page = $this->pagesMap[$language][$slug];

        // check the page's status
        if ($page['active'] != PageNestedSet::PAGE_STATUS_ACTIVE
                || $page['module_status'] != ApplicationAbstractBaseModel::MODULE_STATUS_ACTIVE) {

            return false;
        }

        switch ($type) {
            case PageNestedSet::PAGE_TYPE_SYSTEM :
            case PageNestedSet::PAGE_TYPE_CUSTOM :
                if($page['type'] != $type) {
                    return false;   
                }
                break;

            default :
        }

        // check the page's privacy
        if (false == ($result = PagePrivacyUtility::checkPagePrivacy($page['privacy'], 
                $privacyOptions, $trustedPrivacyData))) {

            return false;
        }

        // check the page's visibility
        if (!empty($page['hidden']) && in_array(UserIdentityService::getCurrentUserIdentity()['role'], 
                $page['hidden'])) {

            return false;
        }

        // check for a parent and 
        if (!empty($page['parent'])) {
            if (false === ($parentUrl = $this->getPageUrl($page['parent'], [], $language, false))) {
                return false;
            }

            // build a link (skip the home page)
            if ($this->pagesMap[$language][$page['parent']]['level'] > 1) {
                $slug = $parentUrl . '/' . $slug;
            }
        }

        return $slug;
    }
}
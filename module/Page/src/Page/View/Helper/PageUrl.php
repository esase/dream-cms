<?php
namespace Page\View\Helper;

use Page\Model\Page as PageModel;
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
     * @return string|boolean
     */
    public function __invoke($slug, array $privacyOptions = [], $language = null)
    {
        // compare the slug for home page 
        if ($this->homePage == $slug) {
            return null;
        }

        if (!$language) {
            $language = LocalizationService::getCurrentLocalization()['language'];
        }

        if (isset($this->definedUrls[$language]) 
                && array_key_exists($slug, $this->definedUrls[$language])) {

            return $this->definedUrls[$language][$slug];
        }

        $pageUrl = $this->getPageUrl($slug, $privacyOptions, $language);
        $this->definedUrls[$language][$slug] = $pageUrl;

        return $pageUrl;
    }

    /**
     * Get page url
     *
     * @param string $slug
     * @param array $privacyOptions
     * @param string $language
     * @return string|boolean
     */
    protected function getPageUrl($slug, array $privacyOptions = [], $language) 
    {
        if (!array_key_exists($slug, $this->pagesMap[$language])) {
            return false;
        }

        // get a page info
        $page = $this->pagesMap[$language][$slug];

        // check the page's status
        if ($page['active'] != PageModel::PAGE_STATUS_ACTIVE) {
            return false;
        }

        // check the page's privacy
        if (false == ($result = PagePrivacyUtility::checkPagePrivacy($page['privacy'], 
                $privacyOptions))) {

            return false;
        }

        // check the page's visibility
        if (!empty($page['hidden']) && in_array(UserIdentityService::getCurrentUserIdentity()['role'], 
                $page['hidden'])) {

            return false;
        }

        // check for a parent and skip the home page
        if (!empty($page['parent']) && $this->pagesMap[$language][$page['parent']]['level'] > 1) {
            if (false === ($parentUrl = $this->getPageUrl($page['parent'], $privacyOptions, $language))) {
                return false;
            }

            // build a link
            $slug = $parentUrl . '/' . $slug;
        }

        return $slug;
    }
}
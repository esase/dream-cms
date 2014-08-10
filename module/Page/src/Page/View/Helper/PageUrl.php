<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;
use User\Service\UserIdentity as UserIdentityService;
use Page\Model\Page as PageModel;

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
     * Class constructor
     *
     * @param array $pagesMap
     */
    public function __construct(array $pagesMap = [])
    {
        $this->pagesMap = $pagesMap;
    }

    /**
     * Page url
     *
     * @param string slug
     * @return string|boolean
     */
    public function __invoke($slug)
    {
        if (array_key_exists($slug, $this->definedUrls)) {
            return $this->definedUrls[$slug];
        }

        $this->definedUrls[$slug] = $this->getPageUrl($slug);
        return $this->definedUrls[$slug];
    }

    /**
     * Get page url
     *
     * @param string $slug
     * @return string|boolean
     */
    protected function getPageUrl($slug) 
    {
        if (!array_key_exists($slug, $this->pagesMap)) {
            return false;
        }

        // get a page info
        $page = $this->pagesMap[$slug];

        // check the extra page's checking and page's status
        if ($page['active'] != PageModel::PAGE_STATUS_ACTIVE 
                || (!empty($page['check']) && false === eval($page['check']))) {

            return false;
        }

        // check the page's permissions
        if (!empty($page['disallowed_roles']) 
                && in_array(UserIdentityService::getCurrentUserIdentity()['role'], $page['disallowed_roles'])) {

            return false;
        }

        // check for a parent and skip the home page
        if (!empty($page['parent']) && $this->pagesMap[$page['parent']]['level'] > 1) {
            if (false === ($parentUrl = $this->getPageUrl($page['parent']))) {
                return false;
            }

            // build a link
            $slug = $parentUrl . '/' . $slug;
        }

        return $slug;
    }
}
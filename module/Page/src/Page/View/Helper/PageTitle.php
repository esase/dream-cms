<?php
 
namespace Page\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Page\Model\Page as PageModel;

class PageTitle extends AbstractHelper
{
    /**
     * Page title
     *
     * @param array $pages
     *      string slug
     *      string title
     *      string meta_description
     *      string meta_keywords
     *      integer module
     *      integer user_menu
     *      integer disable_menu
     *      integer active
     *      string type
     *      string language
     *      integer layout
     *      integer left_key
     *      integer right_key
     *      integer level
     * @return void
     */
    public function __invoke(array $pages)
    {
        $pagesCount = count($pages);

        if ($pagesCount > 1) {
            $pages = array_reverse($pages);
            $index = 0;

            foreach ($pages as $page) {
                $pageTitle = $page['type'] == PageModel::PAGE_TYPE_SYSTEM  
                    ? strip_tags($this->getView()->translate($page['title'])) : strip_tags($page['title']);

                $this->getView()->headTitle($pageTitle);
                $index++;

                // don't show the home page into the title
                if ($index + 1 == $pagesCount) {
                    break;
                }
            }
        }
    }
}
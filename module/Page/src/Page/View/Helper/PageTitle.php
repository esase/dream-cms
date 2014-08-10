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
     *      string  slug
     *      string  title
     *      string  type
     *      string  check
     *      integer level
     * @return void
     */
    public function __invoke(array $pages = [])
    {
        if ($pages) {
            $pages = array_reverse($pages);

            // process pages
            foreach ($pages as $page) {
                $pageTitle = $page['type'] == PageModel::PAGE_TYPE_SYSTEM  
                    ? strip_tags($this->getView()->translate($page['title'])) : strip_tags($page['title']);

                $this->getView()->headTitle($pageTitle);
            }
        }
    }
}
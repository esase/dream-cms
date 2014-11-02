<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageMap extends AbstractHelper
{
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
     * Page map
     *
     * @return array
     */
    public function __invoke()
    {
        return $this->pagesMap;
    }
}
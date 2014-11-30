<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PagePosition extends AbstractHelper
{
    /**
     * Start position
     * @var integer
     */
    protected $startPosition = null;

    /**
     * Page position
     *
     * @param integer $value
     * @param array $options
     *      integer page_number
     *      integer per_page
     *      integer items_count
     *      string order_type
     * @return integer
     */
    public function __invoke($value, array $options)
    {
        if (null === $this->startPosition) {
            $this->startPosition = ($options['page_number'] - 1) * $options['per_page'];

            if ($options['order_type'] == 'desc') {
                $this->startPosition = $options['items_count'] - $this->startPosition + 1;
            }
        }

        $options['order_type'] == 'desc'
            ? $this->startPosition--
            : $this->startPosition++;

        return $this->startPosition;
    }
}
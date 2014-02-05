<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class UrlParamEncode extends AbstractHelper
{
    /**
     * Url params encode
     *
     * @param array  $urlParams
     * @return array
     */
    public function __invoke(array $urlParams)
    {
        foreach ($urlParams as $name => &$value) {
            if (!is_array($value)) {
                $value = $this->encode($value);
            }
            else {
                $value = $this->__invoke($value);
            }
        }

        return $urlParams;
    }

    /**
     * Encode param
     *
     * @param string $value
     * @return string
     */
    protected function encode($value)
    {
        return $value
            ? urlencode($value)
            : null;
    }
}

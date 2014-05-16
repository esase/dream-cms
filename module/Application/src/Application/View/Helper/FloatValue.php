<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Utility\Locale as LocaleUtility;

class FloatValue extends AbstractHelper
{
    /**
     * Get a formatted float value
     *
     * @param float $value
     * @return string
     */
    public function __invoke($value)
    {
        return LocaleUtility::convertToLocalizedValue($value, 'float');
    }
}

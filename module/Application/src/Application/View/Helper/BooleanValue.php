<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\I18n\Translator;

class BooleanValue extends AbstractHelper
{
    /**
     * Translator
     * @var object
     */
    protected $translator;

    /**
     * Class constructor
     *
     * @param object $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Process boolean value
     *
     * @param string|integer $value
     * @param array $options
     *      string  true_string
     *      string false_string
     * @return string
     */
    public function __invoke($value, array $options = array())
    {
        return (int) $value
            ? (!empty($options['true_string']) ? $options['true_string'] : $this->translator->translate('Yes'))
            : (!empty($options['false_string']) ? $options['false_string'] : $this->translator->translate('No'));
    }
}

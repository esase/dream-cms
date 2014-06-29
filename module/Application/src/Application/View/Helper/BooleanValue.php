<?php
 
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Zend\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;

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
    public function __construct(I18nTranslatorInterface $translator)
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

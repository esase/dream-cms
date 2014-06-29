<?php

namespace Application\Form;

use Zend\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Zend\Form\Exception\InvalidArgumentException;

class FormManager
{
    /**
     * Translator
     * @var object
     */
    private $translator;


    /**
     * List of forms instances
     * @var array
     */
    private $instances = array();

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
     * Get instance of specified form
     *
     * @papam string $formName
     * @throws Zend\Form\Exception\InvalidArgumentException
     * @return object|boolean
     */
    public function getInstance($formName)
    {
        if (!class_exists($formName)) {
            return false;
        }

        if (array_key_exists($formName, $this->instances)) {
            return $this->instances[$formName];
        }

        $form = new $formName($this->translator);

        if (!$form instanceof CustomFormInterface) {
            throw new InvalidArgumentException(sprintf($formName . ' must be an object implementing CustomFormInterface'));
        }

        $this->instances[$formName] = $form;
        return $this->instances[$formName];
    }
}

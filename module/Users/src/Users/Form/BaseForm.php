<?php

namespace Users\Form;
 
use Zend\Form\Form;
use Zend\Form\FormInterface;
 
class BaseForm extends Form 
{
    /**
     * List of ignored fields
     *
     * @var array
     */
    protected $ignored = array();

    /**
     * Translator
     *
     * @var object
     */
    protected $translator;

    /**
     * Class constructor
     * 
     * @param string $formName
     * @param object $translator
     */
    public function __construct($formName, $translator) 
    {
        $this->translator = $translator;

        // ignore some default fields
        $this->ignored = array('csrf', 'submit');
        $this->setAttribute('method', 'post');

        parent::__construct($formName);
    }

    /**
     * Retrieve the validated data
     *
     * By default, retrieves normalized values; pass one of the
     * FormInterface::VALUES_* constants to shape the behavior.
     *
     * @param  int $flag
     * @return array|object
     * @throws Exception\DomainException
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $formData = parent::getData($flag);

        // ignore some fields
        foreach ($this->ignored as $fieldName) {
            if (array_key_exists($fieldName, $formData)) {
                unset($formData[$fieldName]);
            }
        }

        return $formData;
    }
}
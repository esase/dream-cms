<?php

namespace Application\Form;
use Zend\Mvc\I18n\Translator;

class AbstractCustomForm implements CustomFormInterface 
{
    /**
     * Form 
     * @var object
     */
    protected $form;

    /**
     * Form method
     * @var string
     */
    protected $method = 'post';

    /**
     * Form name
     * @var string
     */
    protected $formName;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array();

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = array();

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = array();

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
        $this->translator  = $translator;
    }

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            $this->form = new CustomFormBuilder($this->formName, $this->formElements,
                $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Add form elements
     *
     * @return void
     */
    public function addFormElements(array $elements)
    {
        $this->formElements = array_merge($elements, $this->formElements);
    }

    /**
     * Set form elements
     *
     * @return void
     */
    public function setFormElements(array $elements)
    {
        $this->formElements = $elements;
    }
}
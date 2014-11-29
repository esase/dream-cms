<?php
namespace Application\Form;

use Zend\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;

abstract class ApplicationAbstractCustomForm implements ApplicationCustomFormInterface 
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
    protected $formElements = [];

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = [];

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = [];

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
        $this->translator  = $translator;
    }

    /**
     * Get form name
     *
     * @return string
     */
    public function getFormName()
    {
       return $this->formName;
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
            $this->form = new ApplicationCustomFormBuilder($this->formName, $this->formElements,
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
        $this->formElements = array_merge($this->formElements, $elements);
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
<?php
namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;

class ExchnageRate extends AbstractCustomForm 
{
    /**
     * Rate max string length
     */
    const RATE_MAX_LENGTH = 11;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'exchnage-rate';

    /**
     * Exchange rates
     * @var array
     */
    protected $exchangeRates;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ),
    );

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get the form builder
        if (!$this->form) {
            // process exchange rates
            foreach ($this->exchangeRates as $rate) {
                $this->formElements = array_merge(array(0 => array(
                    'name' => $rate['code'],
                    'type' => CustomFormBuilder::FIELD_FLOAT,
                    'label' => $rate['name'],
                    'value' => $rate['rate'],
                    'category' => 'General info',
                    'max_length' => self::RATE_MAX_LENGTH
                )), $this->formElements);
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set exchange rates
     *
     * @param array $exchangeRates
     * @return object fluent interface
     */
    public function setExchangeRates(array $exchangeRates)
    {
        $this->exchangeRates = $exchangeRates;
        return $this;
    }
}
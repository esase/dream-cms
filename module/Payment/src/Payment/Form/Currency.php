<?php

namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Payment\Model\PaymentAdministration as PaymentModelAdministration;

class Currency extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'currency';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Currency code id
     * @var integer
     */
    protected $currencyCodeId;

    /**
     * The primary site currency enabled flag
     * @var boolean
     */
    protected $isEnabledPrimaryCurrency = true;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'code' => array(
            'name' => 'code',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Currency code',
            'required' => true,
            'category' => 'General info',
        ),
        'name' => array(
            'name' => 'name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Currency name',
            'required' => true,
            'category' => 'General info',
        ),
        'primary_currency' => array(
            'name' => 'primary_currency',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Primary site currency',
            'required' => false,
            'category' => 'General info',
        ),
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

            // delete the "primary_currency" field from the form
            if (!$this->isEnabledPrimaryCurrency) {
                unset($this->formElements['primary_currency']);
            }

            // add extra filters
            $this->formElements['code']['filters'] = array(
                array(
                    'name' => 'stringtoupper'
                )
            );

            // add extra validators
            $this->formElements['code']['validators'] = array(
                array(
                    'name' => 'regex',
                    'options' => array(
                        'pattern' => '/^[a-z]{3}$/i',
                        'message' => 'Length of the currency code must be 3 characters and contain only Latin letters'
                    )
                ),
                array (
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateCurrencyCode'),
                        'message' => 'Currency code already used'
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
 
    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(PaymentModelAdministration $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set a currency code id
     *
     * @param integer $currencyCodeId
     * @return object fluent interface
     */
    public function setCurrencyCodeId($currencyCodeId)
    {
        $this->currencyCodeId = $currencyCodeId;
        return $this;
    }

    /**
     * Enable or diasble the primary currecny option in the form
     *
     * @param boolean $enable
     * @return object fluent interface
     */
    public function enabledPrimaryCurrency($enable)
    {
        $this->isEnabledPrimaryCurrency = $enable;
        return $this;
    }

    /**
     * Validate the currency code
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCurrencyCode($value, array $context = array())
    {
        return $this->model->isCurrencyCodeFree($value, $this->currencyCodeId);
    }
}
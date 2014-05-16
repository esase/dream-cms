<?php

namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Utility\Locale as LocaleUtility;

class Coupon extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'coupon';

    /**
     * Discount
     * @var integer
     */
    protected $discount;

    /**
     * Date start
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     * @var integer
     */
    protected $dateEnd;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'discount' => array(
            'name' => 'discount',
            'type' => CustomFormBuilder::FIELD_FLOAT,
            'label' => 'Discount',
            'required' => true,
            'category' => 'General info',
            'description' => 'Percentage ratio'
        ),
        'date_start' => array(
            'name' => 'date_start',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Activation date',
            'category' => 'Miscellaneous info',
        ),
        'date_end' => array(
            'name' => 'date_end',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Deactivation date',
            'category' => 'Miscellaneous info',
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
            // fill the form with default values
            $this->formElements['discount']['value'] = $this->discount;
            $this->formElements['date_start']['value'] = $this->dateStart;
            $this->formElements['date_end']['value'] = $this->dateEnd;

            // add extra validators
            $this->formElements['discount']['validators'] = array(
                array (
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateDiscount'),
                        'message' => 'The discount must be more than 0 and less or equal 100'
                    )
                )
            );

            $this->formElements['date_end']['validators'] = array(
                array (
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateDateEnd'),
                        'message' => 'The deactivation date must be more than activation date'
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a discount
     *
     * @param integer $discount
     * @return object fluent interface
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * Set a date start
     *
     * @param integer $dateStart
     * @return object fluent interface
     */
    public function setDateStart($dateStart)
    {
        if ((int) $dateStart) {
            $this->dateStart = $dateStart;
        }

        return $this;
    }

    /**
     * Set a date end
     *
     * @param integer $dateEnd
     * @return object fluent interface
     */
    public function setDateEnd($dateEnd)
    {
        if ((int) $dateEnd) {
            $this->dateEnd = $dateEnd;
        }

        return $this;
    }

    /**
     * Validate the discount
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateDiscount($value, array $context = array())
    {
        $value = LocaleUtility::convertFromLocalizedValue($value, 'float');
        return $value > 0 && $value <= 100;
    }

    /**
     * Validate the date end
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateDateEnd($value, array $context = array())
    {
        // compare the date start and date end 
        if (!empty($context['date_start'])) {
            return LocaleUtility::convertFromLocalizedValue($value,
                    'date_unixtime') > LocaleUtility::convertFromLocalizedValue($context['date_start'], 'date_unixtime');
        }

        return true;
    }
 }
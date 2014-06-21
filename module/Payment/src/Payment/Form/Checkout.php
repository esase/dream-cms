<?php

namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Payment\Model\Payment as PaymentModel;

class Checkout extends AbstractCustomForm 
{
    /**
     * Comments string length
     */
    const COMMENTS_MAX_LENGTH = 65535;

    /**
     * First name string length
     */
    const FIRST_NAME_MAX_LENGTH = 100;

    /**
     * Last name string length
     */
    const LAST_NAME_MAX_LENGTH = 100;

    /**
     * Email string length
     */
    const EMAIL_MAX_LENGTH = 50;

    /**
     * Phone string length
     */
    const PHONE_MAX_LENGTH = 50;

    /**
     * Address string length
     */
    const ADDRESS_MAX_LENGTH = 100;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'checkout';

    /**
     * Payments types
     * @var array
     */
    protected $paymentsTypes = array();

    /**
     * Hide payment type
     * @var boolean
     */
    protected $hidePaymentType = false;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'payment_type' => array(
            'name' => 'payment_type',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Payment type',
            'required' => true,
            'category' => 'Order information'
        ),
        'comments' => array(
            'name' => 'comments',
            'type' => CustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Comments',
            'required' => false,
            'category' => 'Order information',
            'max_length' => self::COMMENTS_MAX_LENGTH
        ),
        'first_name' => array(
            'name' => 'first_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'First Name',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::FIRST_NAME_MAX_LENGTH
        ),
        'last_name' => array(
            'name' => 'last_name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Last Name',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::LAST_NAME_MAX_LENGTH
        ),
        'email' => array(
            'name' => 'email',
            'type' => CustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::EMAIL_MAX_LENGTH
        ),
        'phone' => array(
            'name' => 'phone',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::PHONE_MAX_LENGTH
        ),
        'address' => array(
            'name' => 'address',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Address',
            'required' => false,
            'category' => 'Delivery details',
            'max_length' => self::ADDRESS_MAX_LENGTH
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
        // get form builder
        if (!$this->form) {
            // hide a payment type field
            if ($this->hidePaymentType) {
                unset($this->formElements['payment_type']);    
            }else {
                // fill the form with default values
                $this->formElements['payment_type']['values'] = $this->paymentsTypes;  
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set payments types
     *
     * @param array $paymentsTypes
     * @return object fluent interface
     */
    public function setPaymentsTypes(array $paymentsTypes)
    {
        $this->paymentsTypes = $paymentsTypes;
        return $this;
    }

    /**
     * Hide payment type
     *
     * @param boolean $hide
     * @return object fluent interface
     */
    public function hidePaymentType($hide)
    {
        $this->hidePaymentType = $hide;
        return $this;
    }
}
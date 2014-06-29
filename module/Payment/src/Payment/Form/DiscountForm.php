<?php
namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Payment\Model\Payment as PaymentModel;

class DiscountForm extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'discount';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'coupon' => array(
            'name' => 'coupon',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Code',
            'required' => true
        )
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
            // add extra validators
            $this->formElements['coupon']['validators'] = array(
                array (
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateCoupon'),
                        'message' => 'The discount code not found or not activated'
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set the model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(PaymentModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Validate the coupon
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCoupon($value, array $context = array())
    {
        return $this->model->getActiveCouponInfo($value) ? true : false;
    }
}
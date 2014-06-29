<?php
namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Payment\Model\Base as PaymentModelBase;

class CouponFilter extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = array('submit');

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'slug' => array(
            'name' => 'slug',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Code'
        ),
        'discount' => array(
            'name' => 'discount',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Discount'
        ),
        'used' => array(
            'name' => 'used',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Used',
            'values' => array(
                PaymentModelBase::COUPON_USED  => 'Yes',
                PaymentModelBase::COUPON_NOT_USED => 'No'
            )
        ),
        'start' => array(
            'name' => 'start',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Activation date'
        ),
        'end' => array(
            'name' => 'end',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Deactivation date'
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        )
    );
}
<?php

namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Payment\Model\Base as PaymentModelBase;

class UserTransactionFilter extends AbstractCustomForm 
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
        'paid' => array(
            'name' => 'paid',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Paid',
            'values' => array(
                PaymentModelBase::TRANSACTION_PAID  => 'Yes',
                PaymentModelBase::TRANSACTION_NOT_PAID => 'No'
            )
        ),
        'date' => array(
            'name' => 'date',
            'type' => CustomFormBuilder::FIELD_DATE,
            'label' => 'Date'
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        )
    );
}
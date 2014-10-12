<?php
namespace Page\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;

class PageFilter extends ApplicationAbstractCustomForm 
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
    protected $notValidatedElements = ['submit'];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        0 => [
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page is active',
            'values' => [
               'active' => 'Yes',
               'not_active' => 'No'
            ]
        ],
        1 => [
            'name' => 'redirect',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Redirect',
            'values' => [
               'redirected' => 'Yes',
               'not_redirected' => 'No'
            ]
        ],
        2 => [
            'name' => 'page_id',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN
        ],
        3 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}
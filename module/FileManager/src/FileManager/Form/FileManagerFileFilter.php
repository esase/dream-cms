<?php
namespace FileManager\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class FileManagerFileFilter extends ApplicationAbstractCustomForm 
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
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name'
        ],
        1 => [
            'name' => 'file_type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Files type',
            'values' => [
                'image' => 'Images',
                'media' => 'Media'
            ]
        ],
        2 => [
            'name' => 'type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Show only',
            'values' => [
                'directory' => 'Directories',
                'file' => 'Files'
            ]
        ],
        3 => [
            'name' => 'path',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN
        ],
        4 => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}
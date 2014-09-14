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
    protected $notValidatedElements = array('submit');

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        0 => array(
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name'
        ),
        1 => array(
            'name' => 'file_type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Files type',
            'values' => array(
                'image' => 'Images',
                'media' => 'Media'
            )
        ),
        2 => array(
            'name' => 'type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Show only',
            'values' => array(
                'directory' => 'Directories',
                'file' => 'Files'
            )
        ),
        3 => array(
            'name' => 'path',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN
        ),
        4 => array(
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        )
    );
}
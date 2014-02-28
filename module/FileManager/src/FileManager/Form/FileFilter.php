<?php

namespace FileManager\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;

class FileFilter extends AbstractCustomForm 
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
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Name'
        ),
        1 => array(
            'name' => 'file_type',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Files type',
            'values' => array(
                'image' => 'Images',
                'media' => 'Media'
            )
        ),
        2 => array(
            'name' => 'type',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Show only',
            'values' => array(
                'directory' => 'Directories',
                'file' => 'Files'
            )
        ),
        3 => array(
            'name' => 'path',
            'type' => CustomFormBuilder::FIELD_HIDDEN
        ),
        4 => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        )
    );
}
<?php

namespace FileManager\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Service\Service as ApplicationService;
use Application\Utility\FileSystem as FileSystemUtility;

class File extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'file';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'file' => array(
            'name' => 'file',
            'type' => CustomFormBuilder::FIELD_FILE,
            'label' => 'File',
            'required' => true,
            'category' => 'General info',
            'description' => 'New file description',
            'description_params' => array(),
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
            // add descriptions params
            $this->formElements['file']['description_params'] = array(
                strtolower(ApplicationService::getSetting('file_manager_allowed_extensions')),
                FileSystemUtility::convertBytes((int) ApplicationService::getSetting('file_manager_allowed_size'))                
            );

            // add extra validators
            $this->formElements['file']['validators'] = array(
                array(
                    'name' => 'fileextension',
                    'options' => array(
                        'extension' => explode(',', strtolower(ApplicationService::getSetting('file_manager_allowed_extensions')))
                    )
                ),
                array(
                    'name' => 'filesize',
                    'options' => array(
                        'max' => (int) ApplicationService::getSetting('file_manager_allowed_size')
                    )
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
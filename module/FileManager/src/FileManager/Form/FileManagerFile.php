<?php
namespace FileManager\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;

class FileManagerFile extends ApplicationAbstractCustomForm 
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
    protected $formElements = [
        'file' => [
            'name' => 'file',
            'type' => ApplicationCustomFormBuilder::FIELD_FILE,
            'label' => 'File',
            'required' => true,
            'description' => 'New file description',
            'description_params' => []
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

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
            $this->formElements['file']['description_params'] = [
                strtolower(SettingService::getSetting('file_manager_allowed_extensions')),
                FileSystemUtility::convertBytes((int) SettingService::getSetting('file_manager_allowed_size'))                
            ];

            // add extra validators
            $this->formElements['file']['validators'] = [
                [
                    'name' => 'fileextension',
                    'options' => [
                        'extension' => explode(',', strtolower(SettingService::getSetting('file_manager_allowed_extensions')))
                    ]
                ],
                [
                    'name' => 'filesize',
                    'options' => [
                        'max' => (int) SettingService::getSetting('file_manager_allowed_size')
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
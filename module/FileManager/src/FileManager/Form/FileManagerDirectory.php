<?php
namespace FileManager\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Application\Service\Service as ApplicationService;

class FileManagerDirectory extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'directory';

    /**
     * Current path
     * @var string
     */
    protected $path;

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'category' => 'General info',
            'description' => 'New directory description',
            'description_params' => [],
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ],
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
            // add extra filters
            $this->formElements['name']['filters'] = [
                [
                    'name' => 'stringtolower'
                ]
            ];

            // add descriptions params
            $this->formElements['name']['description_params'] = [
                ApplicationService::getSetting('file_manager_file_name_length')
            ];

            // add extra validators
            $this->formElements['name']['validators'] = [
                [
                    'name' => 'regex',
                    'options' => [
                        'pattern' => '/^[' . FileManagerBaseModel::getDirectoryNamePattern() . ']+$/',
                        'message' => 'You can use only latin, numeric and underscore symbols'
                    ]
                ],
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateExistingDirectory'],
                        'message' => 'Directory already exist'
                    ]
                ]
            ];

            // add a directory name length limit
            $this->formElements['name']['max_length'] = (int) ApplicationService::getSetting('file_manager_file_name_length');

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set current path
     *
     * @param string $path
     * @return object fluent interface
     */
    public function setPath($path)
    {
        $this->path = FileManagerBaseModel::processDirectoryPath($path);
        return $this;
    }

    /**
     * Set model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(FileManagerBaseModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Validate existing directory
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateExistingDirectory($value, array $context = [])
    {
        // get a full path
        if (false !== ($fullPath = $this->model->getUserDirectory($this->path))) {
            if (file_exists($fullPath . '/' . $value)) {
                return !is_dir($fullPath . '/' . $value);
            }

            return true;
        }

        return false;
    }
}
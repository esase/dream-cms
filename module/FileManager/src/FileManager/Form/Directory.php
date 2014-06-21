<?php

namespace FileManager\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use FileManager\Model\Base as BaseModel;
use Application\Service\Service as ApplicationService;

class Directory extends AbstractCustomForm 
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
    protected $formElements = array(
        'name' => array(
            'name' => 'name',
            'type' => CustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'category' => 'General info',
            'description' => 'New directory description',
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
            // add extra filters
            $this->formElements['name']['filters'] = array(
                array(
                    'name' => 'stringtolower'
                )
            );

            // add descriptions params
            $this->formElements['name']['description_params'] = array(
                ApplicationService::getSetting('file_manager_file_name_length')
            );

            // add extra validators
            $this->formElements['name']['validators'] = array(
                array(
                    'name' => 'regex',
                    'options' => array(
                        'pattern' => '/^[' . BaseModel::getDirectoryNamePattern() . ']+$/',
                        'message' => 'You can use only latin, numeric and underscore symbols'
                    )
                ),
                array(
                    'name' => 'callback',
                    'options' => array(
                        'callback' => array($this, 'validateExistingDirectory'),
                        'message' => 'Directory already exist'
                    )
                )
            );

            // add a directory name length limit
            $this->formElements['name']['max_length'] = (int) ApplicationService::getSetting('file_manager_file_name_length');

            $this->form = new CustomFormBuilder($this->formName,
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
        $this->path = BaseModel::processDirectoryPath($path);
        return $this;
    }

    /**
     * Set model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(BaseModel $model)
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
    public function validateExistingDirectory($value, array $context = array())
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
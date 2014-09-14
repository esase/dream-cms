<?php
namespace FileManager\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Service\Service as ApplicationService;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;

class FileManagerEdit extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'edit';
    
    /**
     * Is directory
     * @var boolean
     */
    protected $isDirectory = false;

    /**
     * File name
     * @var string
     */
    protected $fileName;

    /**
     * Full file's path
     * @var string
     */
    protected $fullFilePath;

    /**
     * User's path
     * @var string
     */
    protected $userPath;

    /**
     * Full user's path
     * @var string
     */
    protected $fullUserPath;

    /**
     * Max file name length
     * @var integer
     */
    protected $maxFileNameLength;

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
            'category' => 'General info'
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

            // set the default file name
            $this->formElements['name']['value'] = $this->isDirectory
                ? $this->fileName
                : FileSystemUtility::getFileName($this->fileName); // remove the file's extension

            // init the max file name length
            $this->maxFileNameLength = $this->isDirectory
                ? (int) ApplicationService::getSetting('file_manager_file_name_length')
                : (int) ApplicationService::getSetting('file_manager_file_name_length') - (strlen(FileSystemUtility::getFileExtension($this->fileName)) + 1);

            // init a directory or file settings
            $this->isDirectory ? $this->initDirectorySettings() : $this->initFileSettings();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    
    /**
     * Init directory settings
     */
    protected function initDirectorySettings()
    {
        // add descriptions params
        $this->formElements['name']['description'] = 'New directory description';
        $this->formElements['name']['description_params'] = [
            $this->maxFileNameLength
        ];

        // add extra validators for the file
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
                    'callback' => [$this, 'validatePossibilityMovingDirectory'],
                    'message' => 'You can not move a directory to itself or its subdirectories'
                ]
            ],
            [
                'name' => 'callback',
                'options' => [
                    'callback' => [$this, 'validateExistingDirectory'],
                    'message' => 'Directory already exist'
                ]
            ],
        ];

        $this->formElements['name']['max_length'] = (int) $this->maxFileNameLength;
    }

    /**
     * Init file settings
     */
    protected function initFileSettings()
    {
        // add descriptions params
        $this->formElements['name']['description'] = 'File edit name description';
        $this->formElements['name']['description_params'] = [
            $this->maxFileNameLength
        ];

        // add extra validators for the file
        $this->formElements['name']['validators'] = [
            [
                'name' => 'regex',
                'options' => [
                    'pattern' => '/^[' . FileManagerBaseModel::getFileNamePattern() . ']+$/',
                    'message' => 'You can only use the following characters: Latin, numeric, underscore, dot, dash, bracket'
                ]
            ],
            [
                'name' => 'callback',
                'options' => [
                    'callback' => [$this, 'validateExistingFile'],
                    'message' => 'File already exists in selected directory'
                ]
            ],
        ];

        $this->formElements['name']['max_length'] = (int) $this->maxFileNameLength;
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
        $newFilePath = $this->fullUserPath . $value;
        $oldFilePath = $this->fullFilePath . $this->fileName;

        if ($newFilePath != $oldFilePath) {
            if (file_exists($newFilePath)) {
                return !is_dir($newFilePath);
            }
        }

        return true;
    }

    /**
     * Validate possibility moving directory 
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validatePossibilityMovingDirectory($value, array $context = [])
    {
        $newDirectoryPathArray = explode('/', $this->fullUserPath . $value);
        $oldDirectoryPathArray = explode('/', $this->fullFilePath . $this->fileName);
        $oldDirectoryPathCount = count($oldDirectoryPathArray);

        // compare paths
        $oldDirectoryPath = implode('', $oldDirectoryPathArray);
        $newDirectoryPath = implode('', array_slice($newDirectoryPathArray, 0, $oldDirectoryPathCount));

        if ($oldDirectoryPath != $newDirectoryPath || ($oldDirectoryPath ==
                $newDirectoryPath && $oldDirectoryPathCount == count($newDirectoryPathArray))) {

            return true;
        }

        return false;
    }

    /**
     * Validate existing file
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateExistingFile($value, array $context = [])
    {
        $newFilePath = $this->fullUserPath . $value . '.' . FileSystemUtility::getFileExtension($this->fileName);
        $oldFilePath = $this->fullFilePath . $this->fileName;

        if ($newFilePath != $oldFilePath) {
            if (file_exists($newFilePath)) {
                return is_dir($newFilePath);
            }
        }

        return true;
    }

    /**
     * Is a directory
     *
     * @param boolean $isDirectory
     * @return object fluent interface
     */
    public function isDirectory($isDirectory)
    {
        $this->isDirectory = $isDirectory;
        return $this;
    }

    /**
     * Set a file name
     *
     * @param string $fileName
     * @return object fluent interface
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Set a a full file's path
     *
     * @param string $fullFilePath
     * @return object fluent interface
     */
    public function setFullFilePath($fullFilePath)
    {
        $this->fullFilePath = $fullFilePath;
        return $this;
    }

    /**
     * Set a user's path
     *
     * @param string $userPath
     * @return object fluent interface
     */
    public function setUserPath($userPath)
    {
        $this->userPath = $userPath;
        return $this;
    }

    /**
     * Set a full user's path
     *
     * @param string $fullUserPath
     * @return object fluent interface
     */
    public function setFullUserPath($fullUserPath)
    {
        $this->fullUserPath = $fullUserPath;
        return $this;
    }
}
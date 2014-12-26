<?php
namespace Application\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Utility\ApplicationFtp as ApplicationFtpUtility;
use Exception;

class ApplicationModule extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'module';

    /**
     * Host
     * @var string
     */
    protected $host;

    /**
     * Ftp utility
     * @var object|boolean
     */
    protected $ftpUtility = false;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'login' => [
            'name' => 'login',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Login',
            'required' => true,
            'category' => 'FTP access'
        ],
        'password' => [
            'name' => 'password',
            'type' => ApplicationCustomFormBuilder::FIELD_PASSWORD,
            'label' => 'Password',
            'required' => true,
            'category' => 'FTP access'
        ],
        'module' => [
            'name' => 'module',
            'type' => ApplicationCustomFormBuilder::FIELD_FILE,
            'label' => 'Module',
            'required' => true,
            'category' => 'FTP access',
            'description' => 'Upload new module description'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
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
            // add extra validators
            $this->formElements['module']['validators'] = [
                [
                    'name' => 'fileextension',
                    'options' => [
                        'extension' => 'zip'
                    ]
                ]
            ];

            $this->formElements['login']['validators'] = [
                [
                    'name' => 'callback',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'callback' => [$this, 'validateFtpConnection'],
                        'message' => 'FTP server is not responding or you entered wrong login data'
                    ]
                ],
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateFtpSystemDirs'],
                        'message' => 'Your FTP account does not allow use the system dirs'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a host
     *
     * @param string $host
     * @return object fluent interface
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Validate FTP connection
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateFtpConnection($value, array $context = [])
    {
        try {
            $ftpUtility = $this->connectToFtp($value, $context['password']);
        }
        catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Validate FTP system dirs
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateFtpSystemDirs($value, array $context = [])
    {
        try {
            $ftpUtility = $this->connectToFtp($value, $context['password']);
            if ($ftpUtility->isDirExists('module')
                    && $ftpUtility->isDirExists(basename(APPLICATION_PUBLIC) . '/layout/base')) {

                return true;
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        return false;
    }

    /**
     * Connect to FTP server
     *
     * @param string $login
     * @param string $password
     * @return object ApplicationFtpUtility
     */
    protected function connectToFtp($login, $password)
    {
        if (false === $this->ftpUtility) {
            $this->ftpUtility = new ApplicationFtpUtility($this->host, $login, $password);
        }

        return $this->ftpUtility;
    }
}
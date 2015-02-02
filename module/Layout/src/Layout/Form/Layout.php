<?php
namespace Layout\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationModule;
use Application\Service\Application as ApplicationService;
use Exception;

class Layout extends ApplicationModule 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'layout';

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
        'file' => [
            'name' => 'layout',
            'type' => ApplicationCustomFormBuilder::FIELD_FILE,
            'label' => 'Layout',
            'required' => true,
            'category' => 'FTP access',
            'description' => 'Upload layout description'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ],
    ];

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
            if ($ftpUtility->isDirExists(ApplicationService::getModulePath(false)) && $ftpUtility->
                        isDirExists(basename(APPLICATION_PUBLIC) . '/' . ApplicationService::getLayoutPath(false))) {

                return true;
            }
        }
        catch (Exception $e) {
            return false;
        }

        return false;
    }
}
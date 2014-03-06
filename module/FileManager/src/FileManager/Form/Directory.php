<?php

namespace FileManager\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use FileManager\Model\Base as BaseModel;

class Directory extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'directory';

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
        ),
        'csrf' => array(
            'name' => 'csrf',
            'type' => CustomFormBuilder::FIELD_CSRF
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
        //TODO:
        //1. check exsisting directory
        //2. check nested level
        //3 check directory length
        
        // get form builder
        if (!$this->form) {
            // add extra validators
            $this->formElements['name']['validators'] = array(
                array(
                    'name' => 'regex',
                    'options' => array(
                        'pattern' => '/^[' . BaseModel::getDirectoryNamePattern() . ']+$/',
                        'message' => 'You can use only latin and numeric symbols'
                    )
                )
            );

            // add extra filters
            $this->formElements['name']['filters'] = array(
                array(
                    'name' => 'stringtolower'
                )
            );

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
}
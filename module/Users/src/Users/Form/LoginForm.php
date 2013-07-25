<?php

namespace Users\Form;
 
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Users\Form\CarForm; 

class LoginForm extends BaseForm 
{
    /**
     * Class constructor
     * 
     * @param object $translator
     */
    public function __construct($translator) 
    {
        parent::__construct('login', $translator);

        $this->add(array(
            'name' => 'nickname',
            'attributes' => array(
                'type' => 'text',
                'id'   => 'nickname',
                'required' => 1,
            ),
            'options' => array(
                'label' => '*' . $this->translator->translate('NickName'),
            ),
        ));

        $this->add(array(
            'name' => 'password',
            'attributes' => array(
                'type' => 'password',
                'id'   => 'password',
                'required' => 1,
            ),
            'options' => array(
                'label' => '*' . $this->translator->translate('Password'),
            ),
        ));

        $this->add(array( 
            'name' => 'csrf', 
            'type' => 'csrf', 
        ));

        $this->add(array(
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => array(
                'id' => 'submit',
                'value' => $this->translator->translate('Submit'),
                'class' => 'btn btn-primary'
            ),
            'options' => array(
                'label' => ' ',
            ),
        ));

        $this->setInputFilter($this->getFilters());
    }

    /**
     * Get form filters
     */
    protected function getFilters()
    {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput(array(
            'name' => 'nickname',
            'required' => true,
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'password',
            'required' => true,
        )));

        return $inputFilter;
    }
}
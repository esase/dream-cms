<?php

namespace Install\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Exception;

class Install extends Form
{
    /**
     * Prepare elements
     */
    public function prepare()
    {
        $this->add([
            'name' => 'db_host',
            'options' => [
                'label' => 'Database host name',
            ],
            'attributes' => [
                'type'  => 'Zend\Form\Element\Text',
                'class' => 'form-control',
                'required' => true,
                'value' => 'localhost'
            ]
        ]);

        $this->add([
            'name' => 'db_port',
            'options' => [
                'label' => 'Database host port number',
            ],
            'attributes' => [
                'type'  => 'Zend\Form\Element\Text',
                'class' => 'form-control',
                'required' => false,
                'value' => null
            ]
        ]);

        $this->add([
            'name' => 'db_name',
            'options' => [
                'label' => 'Database name',
            ],
            'attributes' => [
                'type'  => 'Zend\Form\Element\Text',
                'class' => 'form-control',
                'required' => true,
                'value' => null
            ]
        ]);

        $this->add([
            'name' => 'db_user',
            'options' => [
                'label' => 'Database user',
            ],
            'attributes' => [
                'type'  => 'Zend\Form\Element\Text',
                'class' => 'form-control',
                'required' => true,
                'value' => null
            ]
        ]);

        $this->add([
            'name' => 'db_password',
            'options' => [
                'label' => 'Database password',
            ],
            'attributes' => [
                'type'  => 'Zend\Form\Element\Text',
                'class' => 'form-control',
                'required' => false,
                'value' => null
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type'  => 'submit',
                'value' => 'Submit',
                'class' => 'btn btn-default btn-submit'
            ]
        ]);

        $this->setInputFilter($this->getInputFilters());
    }

    /**
     * Get input filters
     *
     * @return array
     */
    protected function getInputFilters()
    {
        $inputFilter = new InputFilter();
        $inputFactory = new InputFactory();

        $inputFilter->add($inputFactory->createInput([
            'name' => 'db_host',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => function($value, $data) {
                            try {
                                $adapter = new DbAdapter([
                                    'driver' => 'Pdo_Mysql',
                                    'database' => $data['db_name'],
                                    'username' => $data['db_user'],
                                    'password' => $data['db_password'],
                                    'port'     => $data['db_port'],
                                    'host'     => $value
                                ]);

                                $adapter->getDriver()->getConnection()->connect();
                                return true;
                            }
                            catch (Exception $e) {
                            }

                            return false;
                        },
                        'message' => 'Can\'t connect to the Database'
                    ]
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'db_port',
            'required' => false,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'Zend\Validator\Digits'
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'db_name',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'db_user',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'db_password',
            'required' => false,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
            ]
        ]));

        return $inputFilter;
    }
}
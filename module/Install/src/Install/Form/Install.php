<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     * Admin nickName max string length
     */
    const ADMIN_NICKNAME_MAX_LENGTH = 50;

    /**
     * Admin password max string length
     */
    const ADMIN_PASSWORD_MAX_LENGTH = 50;

    /**
     * Admin email max string length
     */
    const ADMIN_EMAIL_MAX_LENGTH = 50;

    /**
     * Site email max string length
     */
    const SITE_EMAIL_MAX_LENGTH = 50;

    /**
     * Prepare elements
     */
    public function prepare()
    {
        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'db_host',
            'options' => [
                'label' => 'Database host name',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'db_host',
                'value' => 'localhost'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'db_port',
            'options' => [
                'label' => 'Database host port number',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'db_port',
                'value' => '3306',
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'db_name',
            'options' => [
                'label' => 'Database name',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'db_name'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'db_user',
            'options' => [
                'label' => 'Database user',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'db_user'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'db_password',
            'options' => [
                'label' => 'Database password',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => false,
                'id' => 'db_password'
            ]
        ]);

        $this->add([
           'type'  => 'Zend\Form\Element\Email',
            'name' => 'site_email',
            'options' => [
                'label' => 'Site email',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'site_email'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'admin_username',
            'options' => [
                'label' => 'Admin username',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'admin_username'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'admin_password',
            'options' => [
                'label' => 'Admin password',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'admin_password'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Email',
            'name' => 'admin_email',
            'options' => [
                'label' => 'Admin email',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => true,
                'id' => 'admin_email'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Select',
            'name' => 'dynamic_cache',
            'options' => [
                'label' => 'Dynamic cache engine',
                'value_options' => [
                    '' => '',
                    'memcached' => 'Memcached',
                    'apc' => 'Apc',
                    'xcache' => 'Xcache',
                    'wincache' => 'Wincache',
                ]
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => false,
                'id' => 'dynamic_cache'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'memcache_host',
            'options' => [
                'label' => 'Memcache host',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => false,
                'id' => 'memcache_host',
                'value' => 'localhost'
            ]
        ]);

        $this->add([
            'type'  => 'Zend\Form\Element\Text',
            'name' => 'memcache_port',
            'options' => [
                'label' => 'Memcache port',
            ],
            'attributes' => [
                'class' => 'form-control',
                'required' => false,
                'id' => 'memcache_port',
                'value' => '11211',
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type'  => 'submit',
                'value' => 'Submit',
                'class' => 'btn btn-default btn-submit',
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
            'required' => true,
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

        $inputFilter->add($inputFactory->createInput([
            'name' => 'admin_username',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => self::ADMIN_NICKNAME_MAX_LENGTH
                    ]
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'admin_password',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => self::ADMIN_PASSWORD_MAX_LENGTH
                    ]
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'admin_email',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => self::ADMIN_EMAIL_MAX_LENGTH
                    ]
                ],
                [
                    'name' => 'emailAddress'
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'site_email',
            'required' => true,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => self::SITE_EMAIL_MAX_LENGTH
                    ]
                ],
                [
                    'name' => 'emailAddress'
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'dynamic_cache',
            'required' => false,
            'filters' => [
            ],
            'validators' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => function($value, $data) {
                            switch($value) {
                                case 'xcache' :
                                    return extension_loaded('xcache');
                                case 'wincache' :
                                    return extension_loaded('wincache');
                                case 'apc' :
                                    return (version_compare('3.1.6', phpversion('apc')) > 0) || !ini_get('apc.enabled') ? false : true;
                                default :
                                    $v = (string) phpversion('memcached');
                                    $extMemcachedMajorVersion = ($v !== '') ? (int) $v[0] : 0;

                                    return $extMemcachedMajorVersion < 1 ? false : true;
                            }
                        },
                        'message' => 'Extension is not installed'
                    ]
                ]
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'memcache_host',
            'required' => false,
            'filters' => [
                [
                    'name' => 'Zend\Filter\StringTrim'
                ]
            ],
            'validators' => [
            ]
        ]));

        $inputFilter->add($inputFactory->createInput([
            'name' => 'memcache_port',
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

        return $inputFilter;
    }
}
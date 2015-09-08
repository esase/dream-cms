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
namespace Application\Test\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Test\ApplicationBootstrap;
use Localization\Utility\LocalizationLocale as LocaleUtility;
use PHPUnit_Framework_TestCase;
use Locale;

class ApplicationCustomFormBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'test';

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = ApplicationBootstrap::getServiceLocator();
    }

    /**
     * Test the htmlarea field
     */
    public function testHmlAreaField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'htmlarea'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>some content</b>'], false);
        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], '<b>some content</b>');

        // test with js (all dangerous scripts should be stripped off)
        $field = [
            [
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<script>alert("test")</script>some content'], false);
        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], 'some content');
    }

    /**
     * Test the dateunixtime field
     */
    public function testDateUnixTimeField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'text',
                'type' => 'date_unixtime'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value with locale
        $this->setCustomLocale('ru_RU');
        $field = [
            [
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '21 мая 2014 г.'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setCustomLocale('en_US');
        $field = [
            [
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '21 мая 2014 г.'], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the date field
     */
    public function testDateField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'text',
                'type' => 'date'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'date',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value with locale
        $this->setCustomLocale('ru_RU');
        $field = [
            [
                'name' => 'test',
                'type' => 'date',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '21 мая 2014 г.'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setCustomLocale('en_US');
        $field = [
            [
                'name' => 'test',
                'type' => 'date',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '21 мая 2014 г'], false);
        $this->assertFalse($form->isValid());

        // test a date convertation
        $this->setCustomLocale('fr_FR');
        $field = [
            [
                'name' => 'test',
                'type' => 'date',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '2 nov. 2013'], false);
        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], '2013-11-02');
    }

    /**
     * Test the url field
     */
    public function testUrlField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'url'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'url',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'url',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'http://mail.com'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'url',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'mail.com'], false);
        $this->assertFalse($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'url',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>http://mail.com</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'http://mail.com');
    }

    /**
     * Test the multi checkbox field
     */
    public function testMulticheckboxField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'multicheckbox'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ''], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true,
                'values' => [
                    1 => 1,
                    2 => 2
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ['1', '2']], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ['1', '2']], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the checkbox field
     */
    public function testCheckboxField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'checkbox'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'checkbox',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ''], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'checkbox',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '1'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'checkbox',
                'required' => false
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'asdsadas'], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the multi select field
     */
    public function testMultiselectField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'multiselect'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ''], false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true,
                'values' => [
                    1 => 1,
                    2 => 2
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ['1', '2']], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ['1', '2']], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the select field
     */
    public function testSelectField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'select'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'select',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ''], false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'select',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '1'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'select',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '2'], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the radio field
     */
    public function testRadioField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'radio'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'radio',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => ''], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'radio',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '1'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'radio',
                'required' => true,
                'values' => [
                    1 => 1
                ]
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '2'], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the password field
     */
    public function testPasswordField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'password'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'password',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'password',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'some content'], false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'password',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b></b>'], false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = [
            [
                'name' => 'test',
                'type' => 'password',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>bbb</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'bbb');
    }

    /**
     * Test the hidden field
     */
    public function testHiddenField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'hidden'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'some content'], false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b></b>'], false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = [
            [
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>bbb</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'bbb');
    }

    /**
     * Test the email field
     */
    public function testEmailField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'email'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'email',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'email',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'tester@mail.com'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = [
            [
                'name' => 'test',
                'type' => 'email',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'testermail.com'], false);
        $this->assertFalse($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'email',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>tester@mail.com</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'tester@mail.com');
    }

    /**
     * Test the text field
     */
    public function testTextField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'text'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'text',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'text',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'some content'], false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'text',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b></b>'], false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = [
            [
                'name' => 'test',
                'type' => 'text',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>bbb</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'bbb');
    }

    /**
     * Test the textarea field
     */
    public function testTextareaField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'test',
                'type' => 'textarea'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'some content'], false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = [
            [
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b></b>'], false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = [
            [
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '<b>bbb</b>'], false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'bbb');
    }

    /**
     * Test the integer field
     */
    public function testIntegerField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'text',
                'type' => 'integer'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = [
            [
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 1], false);
        $this->assertTrue($form->isValid());

        // pass a float value
        $field = [
            [
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 0.5], false);
        $this->assertFalse($form->isValid());

        // test not required value with defined wrong value
        $field = [
            [
                'name' => 'test',
                'type' => 'integer',
                'required' => false
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 'some content'], false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the float field
     */
    public function testFloatField()
    {
        // test with not required value
        $field = [
            [
                'name' => 'text',
                'type' => 'float'
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = [
            [
                'name' => 'test',
                'type' => 'float',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData([], false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $this->setCustomLocale('en_US');
        $field = [
            [
                'name' => 'test',
                'type' => 'float',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => 0.5], false);
        $this->assertTrue($form->isValid());

        // test a correct value with locale
        $this->setCustomLocale('ru_RU');
        $field = [
            [
                'name' => 'test',
                'type' => 'float',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '0,5'], false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setCustomLocale('en_US');
        $field = [
            [
                'name' => 'test',
                'type' => 'float',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '0,5'], false);
        $this->assertFalse($form->isValid());

        // test a float conversion
        $this->setCustomLocale('fr_FR');
        $field = [
            [
                'name' => 'test',
                'type' => 'float',
                'required' => true
            ]
        ];

        $form  = new ApplicationCustomFormBuilder($this->formName, $field, $this->serviceLocator->get('Translator'));
        $form->setData(['test' => '0,5'], false);

        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], 0.5);
    }

    /**
     * Set custom locale
     *
     * @param string $locale
     * @return void
     */
    protected function setCustomLocale($locale)
    {
        Locale::setDefault($locale);
        LocaleUtility::setLocale($locale);
    }
}

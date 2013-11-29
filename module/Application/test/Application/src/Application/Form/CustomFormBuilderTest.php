<?php

namespace Application\Test\Service;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Application\Form\CustomFormBuilder;
use Locale;
use IntlDateFormatter;
use Application\Utility\Locale as LocaleUtility;

class CustomFormBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'test';

    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = ApplicationBootstrap::getServiceManager();
    }

    /**
     * Test the htmlarea field
     */
    public function testHmlAreaField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'htmlarea'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>some content</b>'), false);
        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], '<b>some content</b>');

        // test with js (all dangerous scripts should be stripped off)
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'htmlarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<script>alert("test")</script>some content'), false);
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
        $field = array(
            0 => array(
                'name' => 'text',
                'type' => 'date_unixtime'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value with locale
        $this->setLocale('ru_RU');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '02.11.2013'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setLocale('en_US');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '02.11.2013'), false);
        $this->assertFalse($form->isValid());

        // test a date convertation
        $this->setLocale('fr_FR');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date_unixtime',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '2 nov. 2013'), false);
        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], 1383350400);
    }

    /**
     * Test the date field
     */
    public function testDateField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'text',
                'type' => 'date'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value with locale
        $this->setLocale('ru_RU');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '02.11.2013'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setLocale('en_US');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '02.11.2013'), false);
        $this->assertFalse($form->isValid());

        // test a date convertation
        $this->setLocale('fr_FR');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'date',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '2 nov. 2013'), false);
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
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'url'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'url',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'url',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'http://mail.com'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'url',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'mail.com'), false);
        $this->assertFalse($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'url',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>http://mail.com</b>'), false);
        $this->assertTrue($form->isValid());

        $values = $form->getData();
        $this->assertEquals($values['test'], 'http://mail.com');
    }

    /**
     * Test the multicheckbox field
     */
    public function testMulticheckboxField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multicheckbox'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => ''), false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true,
                'values' => array(
                    1 => 1,
                    2 => 2
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => array('1', '2')), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multicheckbox',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => array('1', '2')), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the checkbox field
     */
    public function testCheckboxField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'checkbox'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'checkbox',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => ''), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'checkbox',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '1'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'checkbox',
                'required' => false
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'asdsadas'), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the multiselect field
     */
    public function testMultiselectField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multiselect'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => ''), false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true,
                'values' => array(
                    1 => 1,
                    2 => 2
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => array('1', '2')), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'multiselect',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => array('1', '2')), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the select field
     */
    public function testSelectField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'select'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'select',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => ''), false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'select',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '1'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'select',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '2'), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the radio field
     */
    public function testRadioField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'radio'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'radio',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => ''), false);
        $this->assertFalse($form->isValid());
        
        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'radio',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '1'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'radio',
                'required' => true,
                'values' => array(
                    1 => 1
                )
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '2'), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the password field
     */
    public function testPasswordField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'password'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'password',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'password',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'some content'), false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'password',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b></b>'), false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'password',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>bbb</b>'), false);
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
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'hidden'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'some content'), false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b></b>'), false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'hidden',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>bbb</b>'), false);
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
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'email'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'email',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'email',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'tester@mail.com'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'email',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'testermail.com'), false);
        $this->assertFalse($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'email',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>tester@mail.com</b>'), false);
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
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'text'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'text',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'text',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'some content'), false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'text',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b></b>'), false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'text',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>bbb</b>'), false);
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
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'textarea'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'some content'), false);
        $this->assertTrue($form->isValid());

        // test with html tags
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b></b>'), false);
        $this->assertFalse($form->isValid());

        // test with html tags#2 we should get clean data
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'textarea',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '<b>bbb</b>'), false);
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
        $field = array(
            0 => array(
                'name' => 'text',
                'type' => 'integer'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 1), false);
        $this->assertTrue($form->isValid());

        // pass a float value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'integer',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 0.5), false);
        $this->assertFalse($form->isValid());

        // test not required value with defined wrong value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'integer',
                'required' => false
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 'some content'), false);
        $this->assertFalse($form->isValid());
    }

    /**
     * Test the float field
     */
    public function testFloatField()
    {
        // test with not required value
        $field = array(
            0 => array(
                'name' => 'text',
                'type' => 'float'
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertTrue($form->isValid());

        // test with required value
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'float',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array(), false);
        $this->assertFalse($form->isValid());

        // test a correct value
        $this->setLocale('en_US');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'float',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => 0.5), false);
        $this->assertTrue($form->isValid());

        // test a correct value with locale
        $this->setLocale('ru_RU');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'float',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '0,5'), false);
        $this->assertTrue($form->isValid());

        // test a incorrect value with locale
        $this->setLocale('en_US');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'float',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '0,5'), false);
        $this->assertFalse($form->isValid());

        // test a float convertation
        $this->setLocale('fr_FR');
        $field = array(
            0 => array(
                'name' => 'test',
                'type' => 'float',
                'required' => true
            )
        );

        $form  = new CustomFormBuilder($this->formName, $field, $this->serviceManager->get('Translator'));
        $form->setData(array('test' => '0,5'), false);

        $this->assertTrue($form->isValid());
        $values = $form->getData();
        $this->assertEquals($values['test'], 0.5);
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return void
     */
    protected function setLocale($locale)
    {
        Locale::setDefault($locale);
        LocaleUtility::setLocale($locale);
    }
}

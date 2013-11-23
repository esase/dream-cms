<?php

namespace Application\Form;
 
use Zend\Form\Form;
use Zend\Form\FormInterface;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Form\Exception\InvalidArgumentException;
use Application\Service\Service as ApplicationService;
use Application\Utility\Locale as LocaleUtility;
use Zend\Captcha\Image as CaptchaImage;
use IntlDateFormatter;

class CustomFormBuilder extends Form 
{
    /**
     * Init date script
     * @var boolean
     */
    protected $initDateScript = false;

    /**
     * Init html area script
     * @var boolean
     */
    protected $initHtmlAreaScript = false;

    /**
     * Form custom elements
     * @var array
     */
    protected $customElements;

    /**
     * Translator
     * @var object
     */
    protected $translator;

    /**
     * Input filter
     * @var object
     */
    protected $inputFilter;

    /**
     * Input factory
     * @var object
     */
    protected $inputFactory;

    /**
     * List of ignored fields
     * @var array
     */
    protected $ignoredElements = array();

    /**
     * Default filters
     * @var array
     */
    protected $defaultFilters = array(
        array('name' => 'StripTags'),
        array('name' => 'StringTrim')
    );

    /**
     * Text type
     */
    const FIELD_TEXT = 'text';

    /**
     * Integer type
     */
    const FIELD_INTEGER = 'integer';

    /**
     * Float type
     */
    const FIELD_FLOAT = 'float';

    /**
     * Email type
     */
    const FIELD_EMAIL = 'email';

    /**
     * Hidden type
     */
    const FIELD_HIDDEN  = 'hidden';

    /**
     * Textarea type
     */
    const FIELD_TEXT_AREA = 'textarea';

    /**
     * Password type
     */
    const FIELD_PASSWORD = 'password';

    /**
     * Radio type
     */
    const FIELD_RADIO = 'radio';

    /**
     * Select type
     */
    const FIELD_SELECT = 'select';

    /**
     * Multiselect type
     */
    const FIELD_MULTI_SELECT = 'multiselect';

    /**
     * Checkbox type
     */
    const FIELD_CHECKBOX = 'checkbox';

    /**
     * Multicheckbox type
     */
    const FIELD_MULTI_CHECKBOX = 'multicheckbox';

    /**
     * Url type
     */
    const FIELD_URL = 'url';

    /**
     * Submit type
     */
    const FIELD_SUBMIT = 'submit';

    /**
     * Csrf type
     */
    const FIELD_CSRF = 'csrf';

    /**
     * Csrf timeout
     */
    const CSRF_TIMEOUT = 1200;

    /**
     * Captcha type
     */
    const FIELD_CAPTCHA = 'captcha';

    /**
     * Date type
     */
    const FIELD_DATE = 'date';

    /**
     * Date unixtime type
     */
    const FIELD_DATE_UNIXTIME = 'date_unixtime';

    /**
     * Html area type
     */
    const FIELD_HTML_AREA = 'htmlarea';

    /**
     * Class constructor
     *
     * @param string $formName
     * @param array $formElements
     *      string name required
     *      string type required
     *      string label optional
     *      string category label
     *      boolean|integer required optional
     *      string value optional
     *      array values required for radios, multicheckboxes and selects
     *      string values_provider (PHP function that returns the list of values)
     *      array attributes optional
     *      array filters optional
     *      array validators optional
     * @param object $translator
     * @param array $ignoredElements
     */
    public function __construct($formName, array $formElements, Translator $translator, array $ignoredElements = array()) 
    {
        parent::__construct($formName);

        $useFilters = true;

        // ignored elements
        $this->ignoredElements = array_merge(array('csrf', 'submit'), $ignoredElements);

        $this->translator = $translator;
        $this->inputFilter = new InputFilter();
        $this->inputFactory = new InputFactory();

        // add elements
        foreach ($formElements as $element) {
            $elementType     = isset($element['type']) ? $element['type'] : null;
            $elementName     = isset($element['name']) ? $element['name'] : null;
            $elementRequired = !empty($element['required']) ? true : false;
            $elementValue    = isset($element['value']) ? $element['value'] : null;
            $elementValues   = isset($element['values']) ? $element['values'] : array();
            $elementAttrs    = isset($element['attributes']) && is_array($element['attributes']) ? $element['attributes'] : array();

            if (!empty($element['values_provider'])) {
               $valuesProvider =  eval($element['values_provider']);
                if (!is_array($valuesProvider)) {
                    throw new InvalidArgumentException('Values provider should return only an array');
                }

                $elementValues = array_merge($elementValues, $valuesProvider); 
            }

            if (!$elementType || !$elementName) {
                throw new InvalidArgumentException('Type and name are required');
            }

            // remember all elements
            $this->customElements[$elementName] = $elementType;

            // list of default element validators
            $elementValidators = array();
            $extraOptions = array();

            switch ($elementType) {
                case self::FIELD_HTML_AREA :
                    // add custom filters
                    $element['filters'] = array_merge((isset($element['filters']) ? $element['filters'] : array()), array(
                        array('name' => 'StringTrim'),
                        array(
                            'name' => 'callback',
                            'options' => array(
                                'callback' => function($value) {
                                    return \Users\Service\Service::isAdmin() // don't purify the content when user is the admin
                                        ? $value
                                        : \HTMLPurifierStandalone::purify($value, array(
                                                'Cache.DefinitionImpl' => null,
                                                'HTML.SafeObject' => true,
                                                'Output.FlashCompat' => true
                                        ));
                                }
                            )
                        )
                    ));

                    $this->initHtmlAreaScript = true;
                    $elementAttrs = array_merge(array('class' => 'htmlarea', 'required' => false), $elementAttrs);
                    $elementType  = 'Textarea';
                    break;
                case self::FIELD_DATE :
                case self::FIELD_DATE_UNIXTIME :
                    $elementValidators[] = array(
                        'name' => 'datetime',
                        'options' => array(
                            'dateType' => IntlDateFormatter::MEDIUM //input format
                        )
                    );

                    $this->initDateScript = true;
                    $elementAttrs = array_merge(array('class' => 'date'), $elementAttrs);
                    $elementValue = LocaleUtility::convertToLocalizedValue($elementValue, $elementType);
                    $elementType  = 'Text';
                    break;
                case self::FIELD_SELECT :
                case self::FIELD_RADIO  :    
                    $elementValidators[] = array(
                        'name' => 'inarray',
                        'options' => array(
                            'haystack' => array_keys($elementValues)
                        )
                    );

                    // add empty value
                    if ($elementType == self::FIELD_SELECT) {
                        $elementValues = array_merge(array(
                            '' => ''
                        ), $elementValues);
                    }

                    $elementType  = $elementType == self::FIELD_SELECT
                        ? 'Select'
                        : 'Radio';
                    break;
                case self::FIELD_MULTI_SELECT   :
                case self::FIELD_MULTI_CHECKBOX :
                    if ($elementType == self::FIELD_MULTI_SELECT) {
                        $elementAttrs = array_merge($elementAttrs, array('multiple' => true));
                    }

                    $elementValidators[] = array(
                        'name' => 'callback',
                        'options' => array(
                            'message' => 'The input was not found in the haystack',
                            'callback' => function($values) use ($elementValues) {
                                if (!is_array($values)) {
                                    return false;
                                }

                                foreach ($values as $value) {
                                    if (!array_key_exists($value, $elementValues)) {
                                        return false;
                                    }
                                }

                                return true;
                            }
                        )
                    );

                    $useFilters = false;
                    if ($elementType == self::FIELD_MULTI_CHECKBOX) {
                        $extraOptions = array(
                            'unchecked_value' => '',
                            'use_hidden_element' => true
                        );

                        $elementAttrs = array_merge(array('required' => false), $elementAttrs);
                        $elementType  = 'MultiCheckbox';                        
                    }
                    else {
                        $elementType  = 'Select';
                    }

                    break;
                case self::FIELD_CHECKBOX :
                    $extraOptions = array(
                        'checked_value' => 1,
                        'unchecked_value' => '',
                        'use_hidden_element' => true
                    );

                    $elementValidators[] = array(
                        'name' => 'inarray',
                        'options' => array(
                            'haystack' => array(1)
                        )
                    );

                    if ($elementRequired) {
                        $elementValidators[] = array(
                            'name' => 'callback',
                            'options' => array(
                                'message' => 'You need to select the checkbox',
                                'callback' => function($value) {
                                    return (int) $value >= 1;
                                }
                            )
                        );
                    }

                    $elementType  = 'Checkbox';
                    break;
                case self::FIELD_HIDDEN :
                    $elementType  = 'Hidden';
                    break;
                case self::FIELD_INTEGER :
                    $elementValidators[] = array(
                        'name' => 'digits'
                    );

                    $elementType = 'Text';
                    break;
                case self::FIELD_FLOAT :
                    $elementValue = LocaleUtility::convertToLocalizedValue($elementValue, $elementType);
                    $elementValidators[] = array(
                        'name' => 'float'
                    );

                    $elementType  = 'Text';
                    break;
                case self::FIELD_URL :
                    $elementValidators[] = array(
                        'name' => 'uri',
                        'options' => array(
                            'allowRelative' => false
                        )
                    );

                    $elementType  = 'Url';
                    break;
                case self::FIELD_EMAIL :
                    $elementValidators[] = array(
                        'name' => 'emailAddress'                        
                    );

                    $elementType  = 'Email';
                    break;
                case self::FIELD_TEXT_AREA :
                    $elementType  = 'Textarea';
                    break;
                case self::FIELD_PASSWORD :
                    $elementType  = 'Password';
                    break;
                case self::FIELD_CSRF :
                    $this->addCsrf($elementName);
                    continue(2);
                case self::FIELD_SUBMIT :
                    $this->addSubmit($elementName, (!empty($element['label']) ? $element['label'] : null));
                    continue(2);
                case self::FIELD_CAPTCHA :
                    $this->addCaptcha($elementName, (!empty($element['label']) ? $element['label'] : null));
                    continue(2);
                case self::FIELD_TEXT :
                default :
                    $elementType = 'Text';
            }

            $this->add(array(
                'type' => 'Zend\Form\Element\\' . $elementType,
                'name' => $elementName,
                'attributes' => array_merge(array(
                    'id'   => $elementName,
                    'required' => $elementRequired,
                    'value' => '' !== $elementValue ? $elementValue : null,
                ), $elementAttrs),
                'options' => array_merge($extraOptions, array(
                    'category' =>  !empty($element['category']) ? $element['category'] : null,
                    'value_options' => $elementValues,
                    'label' => !empty($element['label'])
                        ? ($elementRequired
                                ? '*' . $this->translator->translate($element['label'])
                                : $this->translator->translate($element['label']))
                        : null,
                ))
            ));

            // define element filters
            $filters = array();

            if ($useFilters) {
                $filters = isset($element['filters']) ? $element['filters'] : $this->defaultFilters;
            }

            // add validators
            $this->inputFilter->add($this->inputFactory->createInput(array(
                'name' => $elementName,
                'required' => $elementRequired,
                'filters' => $filters,
                'validators' => !empty($element['validators'])
                    ? array_merge($elementValidators, $element['validators'])
                    : $elementValidators                
            )));
        }

        $this->setInputFilter($this->inputFilter);
    }

    /**
     * Retrieve the validated data
     *
     * By default, retrieves normalized values; pass one of the
     * FormInterface::VALUES_* constants to shape the behavior.
     *
     * @param  int $flag
     * @return array|object
     * @throws Exception\DomainException
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $formData = parent::getData($flag);

        // process form data
        $processedData = array();
        foreach ($formData as $fieldName => $fieldValue) {
            // skip all ignored elements
            if (in_array($fieldName, $this->ignoredElements)) {
                continue;
            }

            // convert from localized data
            $processedData[$fieldName] = LocaleUtility::convertFromLocalizedValue($fieldValue, $this->customElements[$fieldName]);

        }

        return $processedData;
    }

    /**
     * Set data to validate and/or populate elements
     *
     * Typically, also passes data on to the composed input filter.
     *
     * @param  array|\ArrayAccess|Traversable $data
     * @param boolean $convertValues
     * @return Form|FormInterface
     * @throws Exception\InvalidArgumentException
     */
    public function setData($data, $convertValues = true)
    {
        // convert localized values
        if ($convertValues) {
            foreach ($data as $fieldName => $fieldValue) {
                if (!isset($this->customElements[$fieldName])) {
                    continue;
                }

                $data[$fieldName] = LocaleUtility::convertToLocalizedValue($fieldValue, $this->customElements[$fieldName]);
            }
        }

        return parent::setData($data);
    }

    /**
     * Add csrf
     *
     * @param string $name
     * @return void
     */
    protected function addCsrf($name)
    {
        $this->add(array( 
            'name' => $name, 
            'type' => self::FIELD_CSRF,
            'options' => array(
                'csrf_options' => array(
                    'timeout' => self::CSRF_TIMEOUT
                )
            )
        ));
    }

    /**
     * Add captcha
     *
     * @param string $name
     * @param string $label
     * @return void
     */
    protected function addCaptcha($name, $label = null)
    {
        // pass captcha image options
        $captchaImage = new CaptchaImage(array(
            'font' => ApplicationService::getCaptchaFontPath(),
            'width' => ApplicationService::getSetting('application_captcha_width'),
            'height' => ApplicationService::getSetting('application_captcha_height'),
            'dotNoiseLevel' => ApplicationService::getSetting('application_captcha_dot_noise'),
            'lineNoiseLevel' => ApplicationService::getSetting('application_captcha_line_noise')
        ));

        $captchaImage->setImgDir(ApplicationService::getCaptchaPath());
        $captchaImage->setImgUrl(ApplicationService::getCaptchaUrl());

        $this->add(array(
            'type' => self::FIELD_CAPTCHA,
            'name' => $name,
            'options' => array(
                'label' => $this->translator->translate(($label ? $label : 'Please verify you are human')),
                'captcha' => $captchaImage
            ),
            'attributes' => array(
                'id' => 'captcha'
            )
        ));
    }

    /**
     * Add submit button
     *
     * @param string $name
     * @param string $label
     * @return void
     */
    protected function addSubmit($name, $label = null)
    {
        $this->add(array(
            'type' => self::FIELD_SUBMIT,
            'name' => $name,
            'attributes' => array(
                'id' => $name,
                'value' => $this->translator->translate(($label ? $label : 'Submit')),
                'class' => 'btn btn-primary'
            ),
            'options' => array(
                'label' => ' ',
            ),
        ));
    }

    /**
     * Init date script
     *
     * @return boolean
     */
    public function initDateScript()
    {
        return $this->initDateScript;
    }

    /**
     * Init htmlarea script
     *
     * @return boolean
     */
    public function initHtmlAreaScript()
    {
        return $this->initHtmlAreaScript;
    }
}
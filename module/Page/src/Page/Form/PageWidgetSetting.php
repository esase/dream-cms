<?php
namespace Page\Form;

use Application\Service\ApplicationSetting as SettingService;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Acl\Service\Acl as AclService;
use Page\Model\PageWidgetSetting as PageWidgetSettingModel;

class PageWidgetSetting extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'settings';

    /**
     * Show visibility settings
     * @var boolean
     */
    protected $showVisibilitySettings = true;

    /**
     * Show cache settings
     * @var boolean
     */
    protected $showCacheSettings = false;

    /**
     * Widget description
     * @var string
     */
    protected $widgetDescription;

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Title max string length
     */
    const TITLE_MAX_LENGTH = 50;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'title' => [
            'name' => 'title',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Widget title',
            'description' => 'The widget uses the system default title',
            'description_params' => [],
            'required' => false,
            'max_length' => self::TITLE_MAX_LENGTH,
            'category' => 'Main settings'
        ],
        'layout' => [
            'name' => 'layout',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Widget layout',
            'required' => false,
            'values' => [],
            'category' => 'Main settings',
        ],
        'visibility_settings' => [
            'name' => 'visibility_settings',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Widget is hidden for',
            'required' => false,
            'values' => [],
            'category' => 'Visibility settings'
        ],
        'cache_ttl' => [
            'name' => 'cache_ttl',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Cache lifetime in seconds',
            'required' => false,
            'values' => [],
            'category' => 'Cache',
            'description' => 'Widget cache description',
            'description_params' => []
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Save'
        ]
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

            // add extra options for the title
            $this->formElements['title']['description_params'] = [
                $this->widgetDescription
            ];

            // add extra options for the cache ttl
            if ($this->showCacheSettings) {
                $this->formElements['cache_ttl']['description_params'] = [
                    (int) SettingService::getSetting('application_dynamic_cache_life_time')
                ];

                // add extra validators
                $this->formElements['cache_ttl']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateCacheTtl'],
                            'message' => 'Enter a correct value'
                        ]
                    ]
                ];
            }
            else {
                unset($this->formElements['cache_ttl']);
            }

            // add extra options for the visibility settings
            if ($this->showVisibilitySettings) {
                // add visibility settings
                $this->formElements['visibility_settings']['values'] = AclService::getAclRoles(false, true);
            }
            else {
                unset($this->formElements['visibility_settings']);
            }

            // fill the form with default values
            $this->formElements['layout']['values'] = $this->model->getWidgetLayouts();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Validate cache ttl
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCacheTtl($value, array $context = [])
    {
        $value = (int) $value;

        return $value >= 0 && $value <= (int) SettingService::getSetting('application_dynamic_cache_life_time');
    }

    /**
     * Show visibility settings
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showVisibilitySettings($show)
    {
        $this->showVisibilitySettings = (bool) $show;
        return $this;
    }

    /**
     * Show cache settings
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showCacheSettings($show)
    {
        $this->showCacheSettings = (bool) $show;
        return $this;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(PageWidgetSettingModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set a widget description
     *
     * @param string $widgetDescription
     * @return object fluent interface
     */
    public function setWidgetDescription($widgetDescription)
    {
        $this->widgetDescription = $widgetDescription;
        return $this;
    }
}
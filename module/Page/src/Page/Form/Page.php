<?php
namespace Page\Form;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Localization\Utility\LocalizationLocale as LocalizationUtility;
use Acl\Service\Acl as AclService;
use Page\Model\PageBase as PageBaseModel;

class Page extends ApplicationAbstractCustomForm 
{
    /**
     * Title max string length
     */
    const TITLE_MAX_LENGTH = 50;

    /**
     * Description max string length
     */
    const DESCRIPTION_MAX_LENGTH = 150;

    /**
     * Slug max string length
     */
    const SLUG_MAX_LENGTH = 100;

    /**
     * Meta keywords max string length
     */
    const META_KEYWORDS_MAX_LENGTH = 150;

    /**
     * Meta description max string length
     */
    const META_DESCRIPTION_MAX_LENGTH = 150;

    /**
     * Meta robots max string length
     */
    const META_ROBOTS_MAX_LENGTH = 50;

    /**
     * Redirect url max string length
     */
    const REDIRECT_URL_MAX_LENGTH = 255;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'page';

    /**
     * Is system page
     * @var boolean
     */
    protected $isSystemPage = false;

    /**
     * Show main menu
     * @var boolean
     */
    protected $showMainMenu = true;

    /**
     * Show site map
     * @var boolean
     */
    protected $showSiteMap = true;

    /**
     * Show xml map
     * @var boolean
     */
    protected $showXmlMap = true;

    /**
     * Show footer menu
     * @var boolean
     */
    protected $showFooterMenu = true;

    /**
     * Show user menu
     * @var boolean
     */
    protected $showUserMenu = true;

    /**
     * Show visibility settings
     * @var boolean
     */
    protected $showVisibilitySettings = true;

    /**
     * Show seo
     * @var boolean
     */
    protected $showSeo = true;

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Page system title
     * @var string
     */
    protected $systemTitle;

    /**
     * Page info
     * @var array
     */
    protected $pageInfo = [];

    /**
     * Page parent
     * @var array
     */
    protected $pageParent = [];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'custom_validate' => [
            'name' => 'custom_validate',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'value' => 1,
            'required' => true,
            'category' => 'General info'
        ],
        'title' => [
            'name' => 'title',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Title',
            'description_params' => [],
            'required' => true,
            'max_length' => self::TITLE_MAX_LENGTH,
            'category' => 'General info'
        ],
        'description' => [
            'name' => 'description',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Description',
            'required' => false,
            'max_length' => self::DESCRIPTION_MAX_LENGTH,
            'category' => 'General info'
        ],
        'slug' => [
            'name' => 'slug',
            'type' => ApplicationCustomFormBuilder::FIELD_SLUG,
            'label' => 'Display name',
            'required' => false,
            'max_length' => self::SLUG_MAX_LENGTH,
            'category' => 'General info',
            'description' => 'The display name will be displayed in the browser bar'
        ],
        'active' => [
            'name' => 'active',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Page is active',
            'required' => false,
            'category' => 'General info'
        ],
        'layout' => [
            'name' => 'layout',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page layout',
            'required' => true,
            'values' => [],
            'category' => 'General info',
            'description' => 'Page layout description'
        ],
        'menu' => [
            'name' => 'menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the main menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'site_map' => [
            'name' => 'site_map',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the site map',
            'required' => false,
            'category' => 'Navigation'
        ],
        'footer_menu' => [
            'name' => 'footer_menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the footer menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'footer_menu_order' => [
            'name' => 'footer_menu_order',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Order in the footer menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'user_menu' => [
            'name' => 'user_menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the user menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'user_menu_order' => [
            'name' => 'user_menu_order',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Order in the user menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'redirect_url' => [
            'name' => 'redirect_url',
            'type' => ApplicationCustomFormBuilder::FIELD_URL,
            'label' => 'Redirect url',
            'required' => false,
            'max_length' => self::REDIRECT_URL_MAX_LENGTH,
            'category' => 'Navigation'
        ],
        'meta_keywords' => [
            'name' => 'meta_keywords',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Meta keywords',
            'required' => false,
            'max_length' => self::META_KEYWORDS_MAX_LENGTH,
            'category' => 'SEO',
            'description' => 'Meta keywords should be separated by comma',
        ],
        'meta_description' => [
            'name' => 'meta_description',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Meta description',
            'required' => false,
            'max_length' => self::META_DESCRIPTION_MAX_LENGTH,
            'category' => 'SEO'
        ],
        'meta_robots' => [
            'name' => 'meta_robots',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Meta robots',
            'required' => false,
            'max_length' => self::META_ROBOTS_MAX_LENGTH,
            'category' => 'SEO',
            'description' => 'Standard commands for search engine robots',
        ],
        'visibility_settings' => [
            'name' => 'visibility_settings',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Page is hidden for',
            'required' => false,
            'values' => [],
            'category' => 'Visibility settings'
        ],
        'page_direction' => [
            'name' => 'page_direction',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Direction',
            'required' => true,
            'value' => 'after',
            'values' => [
                'before' => 'Before',
                'after' => 'After'
            ],
            'category' => 'Page position'
        ],
        'page' => [
            'name' => 'page',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page',
            'required' => true,
            'values' => [],
            'category' => 'Page position'
        ],
        'xml_map' => [
            'name' => 'xml_map',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the xml map',
            'required' => false,
            'category' => 'Xml map'
        ],
        'xml_map_update' => [
            'name' => 'xml_map_update',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page update frequency',
            'required' => true,
            'values' => [
                'always' => 'always',
                'hourly' => 'hourly',
                'daily' => 'daily',
                'weekly' => 'weekly',
                'monthly' => 'monthly',
                'yearly' => 'yearly',
                'never' => 'never',
            ],
            'category' => 'Xml map'
        ],
        'xml_map_priority' => [
            'name' => 'xml_map_priority',
            'type' => ApplicationCustomFormBuilder::FIELD_FLOAT,
            'label' => 'Page priority',
            'required' => true,
            'category' => 'Xml map',
            'description' => 'Xml map priority description',
            'description_params' => []
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
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
            // set default values
            $this->formElements['active']['value'] = (int) SettingService::getSetting('page_new_pages_active');
            $this->formElements['layout']['value'] = (int) SettingService::getSetting('page_new_pages_layout');
            $this->formElements['menu']['value'] = (int) SettingService::getSetting('page_new_pages_in_main_menu');
            $this->formElements['site_map']['value'] = (int) SettingService::getSetting('page_new_pages_in_site_map');
            $this->formElements['footer_menu']['value'] = (int) SettingService::getSetting('page_new_pages_in_footer_menu');
            $this->formElements['footer_menu_order']['value'] = (int) SettingService::getSetting('page_new_pages_footer_menu_order');
            $this->formElements['user_menu']['value'] = (int) SettingService::getSetting('page_new_pages_in_user_menu');
            $this->formElements['user_menu_order']['value'] = (int) SettingService::getSetting('page_new_pages_user_menu_order');
            $this->formElements['xml_map']['value'] = (int) SettingService::getSetting('page_new_pages_in_xml_map');
            $this->formElements['xml_map_update']['value'] = SettingService::getSetting('page_new_pages_xml_map_update');
            $this->formElements['xml_map_priority']['value'] = (float) SettingService::getSetting('page_new_pages_xml_map_priority');
            $this->formElements['visibility_settings']['value'] = SettingService::getSetting('page_new_pages_hidden_for');

            if ($this->isSystemPage) {
                $this->formElements['title']['description'] = 'The page uses the system default title';
                $this->formElements['title']['required'] = false;

                // add descriptions params
                $this->formElements['title']['description_params'] = [
                    $this->systemTitle
                ];

                unset($this->formElements['slug']);
            }

            if (!$this->showMainMenu) {
                unset($this->formElements['menu']);
            }

            if (!$this->showSiteMap) {
                unset($this->formElements['site_map']);
            }

            if (!$this->showXmlMap) {
                unset($this->formElements['xml_map']);
                unset($this->formElements['xml_map_update']);
                unset($this->formElements['xml_map_priority']);
            }

            if (!$this->showFooterMenu) {
                unset($this->formElements['footer_menu']);
                unset($this->formElements['footer_menu_order']);
            }

            if (!$this->showUserMenu) {
                unset($this->formElements['user_menu']);
                unset($this->formElements['user_menu_order']);
            }

            if (!$this->showVisibilitySettings) {
                unset($this->formElements['visibility_settings']);
            }

            if (!$this->showSeo) {
                unset($this->formElements['meta_keywords']);
                unset($this->formElements['meta_description']);
            }

            if (!$this->isSystemPage) {
                // add extra validators
                $this->formElements['slug']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateSlug'],
                            'message' => 'Display name already used'
                        ]
                    ]
                ];
            }

            if ($this->pageInfo) {
                // add extra validators
                $this->formElements['custom_validate']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validatePage'],
                            'message' => 'You cannot move the page into self or into its child pages'
                        ]
                    ]
                ];
            }

            if ($this->showXmlMap) {
                $this->formElements['xml_map_priority']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateXmlMapPriority'],
                            'message' => 'Enter a correct priority value'
                        ]
                    ]
                ];
            }

            // fill the form with default values
            $this->formElements['layout']['values'] = $this->model->getPageLayouts();

            if ($this->showVisibilitySettings) {
                $this->formElements['visibility_settings']['values'] = AclService::getAclRoles(false, true);
            }

            if (null != ($pages = $this->getPages())) {
                $this->formElements['page']['values'] = $pages;
            }
            else {
                unset($this->formElements['page']);
                unset($this->formElements['page_direction']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
  
    /**
     * Get pages
     *
     * @return array
     */
    protected function getPages()
    {
       $pages = [];

       if ($this->pageParent) {
            if (false !== ($childrenPages =
                    $this->model->getAllPageStructureChildren($this->pageParent['id'], true))) {

                $activePageId = null;
                $currentDefined = false;

                foreach ($childrenPages as $children) {
                    // don't draw current page
                    if (!empty($this->pageInfo) && $this->pageInfo['id'] == $children['id']) {
                        $currentDefined = true;
                        continue;
                    }

                    $pageOptions = [
                        'title' => $children['title'],
                        'system_title' => $children['system_title'],
                        'type' => $children['type']
                    ];

                    if (!$currentDefined) {
                        $activePageId = $children['id'];
                    }
                    else if ($currentDefined && !$activePageId) {
                        $activePageId = $children['id'];
                        $this->formElements['page_direction']['value'] = 'before';
                    }

                    $pages[$children['id']] =  ServiceLocatorService::
                            getServiceLocator()->get('viewHelperManager')->get('pageTitle')->__invoke($pageOptions);
                }

                // set dfault value
                if ($activePageId) {
                    $this->formElements['page']['value'] = $activePageId;
                }
            }
       }

       return $pages;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(PageBaseModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set system page
     *
     * @param boolean $system
     * @return object fluent interface
     */
    public function setSystemPage($system)
    {
        $this->isSystemPage = $system;
        return $this;
    }

    /**
     * Show main menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showMainMenu($show)
    {
        $this->showMainMenu = $show;
        return $this;
    }

    /**
     * Show site map
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showSiteMap($show)
    {
        $this->showSiteMap = $show;
        return $this;
    }

    /**
     * Show xml map
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showXmlMap($show)
    {
        $this->showXmlMap = $show;
        return $this;
    }

    /**
     * Show footer menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showFooterMenu($show)
    {
        $this->showFooterMenu = $show;
        return $this;
    }

    /**
     * Show user menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showUserMenu($show)
    {
        $this->showUserMenu = $show;
        return $this;
    }

    /**
     * Show visibility settings
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showVisibilitySettings($show)
    {
        $this->showVisibilitySettings = $show;
        return $this;
    }

    /**
     * Show SEO
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showSeo($show)
    {
        $this->showSeo = $show;
        return $this;
    }

    /**
     * Set page info
     *
     * @param array $pageInfo
     * @return object fluent interface
     */
    public function setPageInfo(array $pageInfo)
    {
        $this->pageInfo = $pageInfo;
        return $this;
    }

    /**
     * Set page parent
     *
     * @param array $pageParent
     * @return object fluent interface
     */
    public function setPageParent(array $pageParent)
    {
        $this->pageParent = $pageParent;
        return $this;
    }

    /**
     * Set page system title
     *
     * @param string $systemTitle
     * @return object fluent interface
     */
    public function setPageSystemTitle($systemTitle)
    {
        $this->systemTitle = $systemTitle;
        return $this;
    }

    /**
     * Validate page
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validatePage($value, array $context = [])
    {
        if (!$this->pageInfo || !$this->pageParent) {
            return true;
        }

        return $this->model->isPageMovable($this->pageInfo['left_key'],
                $this->pageInfo['right_key'], $this->pageInfo['level'], $this->pageParent['left_key']);
    }

    /**
     * Validate slug
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateSlug($value, array $context = [])
    {
        return $this->model->
                isSlugFree($value, (!empty($this->pageInfo['id']) ? $this->pageInfo['id'] : 0));
    }

    /**
     * Validate xml map priority
     * 
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateXmlMapPriority($value, array $context = [])
    {
        $value = (float) LocalizationUtility::convertFromLocalizedValue($value, 'float');
        return $value >= 0 && $value <= 1;
    }
}
<?php
namespace Page\View\Widget;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Localization\Service\Localization as LocalizationService;
use Zend\Http\Response;
use Zend\View\Helper\AbstractHelper;

abstract class PageAbstractWidget extends AbstractHelper implements IPageWidget
{
    /**
     * Widget redirected flag
     * @var boolean
     */
    protected $widgetRedirected = false;

    /**
     * Page Id
     * @var integer
     */
    protected $pageId;

    /**
     * Widget connection Id
     * @var integer
     */
    protected $widgetConnectionId;

    /**
     * Widget position
     * @var string
     */
    protected $widgetPosition;

    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Slug
     * @var string
     */
    protected $slug = null;

    /**
     * Request
     * @var object
     */
    protected $request;

    /**
     * Widget setting model instance
     * @var object  
     */
    private $widgetSettingModel;

    /**
     * Abstract widget
     *
     * @return object fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get slug
     *
     * @param string $defaultValue
     * @return string
     */
    protected function getSlug($defaultValue = null)
    {
        if ($this->slug === null) {
            $this->slug = RouteParamUtility::getParam('slug', $defaultValue);
        }

        return $this->slug; 
    }

    /**
     * Get request
     *
     * @return object
     */
    protected function getRequest()
    {
        if (!$this->request) {
            $this->request = $this->getServiceLocator()->get('Request');
        }

        return $this->request;
    }

    /**
     * Get service locator
     *
     * @return object
     */
    protected function getServiceLocator()
    {
        if (!$this->serviceLocator) {
            $this->serviceLocator = ServiceLocatorService::getServiceLocator();
        }

        return $this->serviceLocator;
    }

    /**
     * Translate
     * 
     * @pram string $key
     * @return string
     */
    protected function translate($key)
    {
        return $this->getServiceLocator()->get('Translator')->translate($key);
    }

    /**
     * Get setting
     *
     * @param string $setting
     * @param string $language
     * @return string|boolean
     */
    protected function getSetting($setting, $language = null)
    {
        return SettingService::getSetting($setting, $language);
    }

    /**
     * Redirect to 
     *
     * @param array $params
     * @param boolean $useReferer
     * @param array $queries
     * @return string
     */
    protected function redirectTo(array $params = [], $useReferer = false, array $queries = [])
    {
        $request = $this->getServiceLocator()->get('Request');
        $this->widgetRedirected = true;

        // check the referer
        if ($useReferer && null != ($referer = $request->getHeader('Referer'))) {
            return $this->getServiceLocator()->
                    get('controllerPluginManager')->get('redirect')->toUrl($referer->uri());
        }

        return $this->getServiceLocator()->
                    get('controllerPluginManager')->get('redirect')->toRoute('page', $params, ['query' => $queries]); 
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @return string
     */
    protected function redirectToUrl($url)
    {
        $this->widgetRedirected = true;
        $request = $this->getServiceLocator()->get('Request');
        return $this->getServiceLocator()->
                get('controllerPluginManager')->get('redirect')->toUrl($url);
    }

    /**
     * Reload page
     *
     * @return string
     */
    protected function reloadPage()
    {
        $this->widgetRedirected = true;
        return $this->getServiceLocator()->
                get('controllerPluginManager')->get('redirect')->toUrl($this->getView()->serverUrl(true));
    }

    /**
     * Get flash messenger
     *
     * @return object
     */
    protected function getFlashMessenger()
    {
        return $this->getServiceLocator()->
                get('controllerPluginManager')->get('flashMessenger');
    }

    /**
     * Get widget setting model
     */
    private function getWidgetSettingModel()
    {
        if (!$this->widgetSettingModel) {
            $this->widgetSettingModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageWidgetSetting');
        }

        return $this->widgetSettingModel;
    }

    /**
     * Get widget setting value
     *
     * @return string|array|boolean
     */
    protected function getWidgetSetting($name)
    {
        $currentlanguage = LocalizationService::getCurrentLocalization()['language'];

        return  $this->getWidgetSettingModel()->
                getWidgetSetting($this->pageId, $this->widgetConnectionId, $name, $currentlanguage);
    }

    /**
     * Set current page id
     *
     * @param integer $pageId
     * @return object fluent interface
     */
    public function setPageId($pageId = 0) 
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * Set widget connection id
     *
     * @param integer $widgetId
     * @return object fluent interface
     */
    public function setWidgetConnectionId($widgetId) 
    {
        $this->widgetConnectionId = $widgetId;
        return $this;
    }

    /**
     * Set widget position
     *
     * @param string $position
     * @return object fluent interface
     */
    public function setWidgetPosition($position) 
    {
        $this->widgetPosition = $position;
        return $this;
    }

    /**
     * Is widget redirected
     *
     * @return boolean
     */
    public function isWidgetRedirected()
    {
        return $this->widgetRedirected;
    }

    /**
     * Get widget connection url
     * 
     * @param array $removeParams
     * @return string
     */
    public function getWidgetConnectionUrl($removeParams = [])
    {
        $url = parse_url($this->getView()->serverUrl(true));

        // build url
        $baseUrlParams = [];
        $baseUrlParams['widget_connection'] = $this->widgetConnectionId;
        $baseUrlParams['widget_position'] = $this->widgetPosition;

        // merge url params
        if (!empty($url['query'])) {
            parse_str($url['query'], $urlParams);
            
            if (!empty($removeParams)) {
                foreach ($removeParams as $param) {
                    if (array_key_exists($param, $urlParams)) {
                        unset($urlParams[$param]);
                    }
                }
            }

            $baseUrlParams = array_merge($urlParams, $baseUrlParams);
        }

        return $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . http_build_query($baseUrlParams);
    }
}
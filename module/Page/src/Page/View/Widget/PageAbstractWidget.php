<?php
namespace Page\View\Widget;
 
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Zend\Http\Response;
use Zend\View\Helper\AbstractHelper;

abstract class PageAbstractWidget extends AbstractHelper implements IPageWidget
{
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
        return  $this->getWidgetSettingModel()->
                getWidgetSetting($this->pageId, $this->widgetConnectionId, $name);
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
}
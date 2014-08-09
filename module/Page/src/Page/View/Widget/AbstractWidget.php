<?php
namespace Page\View\Widget;
 
use Application\Service\ServiceManager;
use Zend\View\Helper\AbstractHelper;

abstract class AbstractWidget extends AbstractHelper implements IWidget
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
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Setting model instance
     * @var object  
     */
    private $settingModel;

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
     * Get service manager
     *
     * @return object
     */
    protected function getServiceManager()
    {
        if (!$this->serviceManager) {
            $this->serviceManager = ServiceManager::getServiceManager();
        }

        return $this->serviceManager;
    }

    /**
     * Get setting model
     */
    private function getSettingModel()
    {
        if (!$this->settingModel) {
            $this->settingModel = $this->getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\WidgetSetting');
        }

        return $this->settingModel;
    }

    /**
     * Get setting value
     *
     * @return string|array|boolean
     */
    protected function getSetting($name)
    {
        return  $this->getSettingModel()->
                getWidgetSetting($this->pageId, $this->widgetConnectionId, $name);
    }

    /**
     * Set current page id
     *
     * @param integer $pageId
     * @return object fluent interface
     */
    public function setPageId($pageId) 
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
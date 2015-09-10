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
     *
     * @var boolean
     */
    protected $widgetRedirected = false;

    /**
     * Page Id
     *
     * @var integer
     */
    protected $pageId;

    /**
     * Widget connection Id
     *
     * @var integer
     */
    protected $widgetConnectionId;

    /**
     * Widget position
     *
     * @var string
     */
    protected $widgetPosition;

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Request
     *
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $request;

    /**
     * Widget setting model instance
     *
     * @var \Page\Model\PageWidgetSetting
     */
    private $widgetSettingModel;

    /**
     * Abstract widget
     *
     * @return \Page\View\Widget\PageAbstractWidget
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
        return  RouteParamUtility::getParam('slug', $defaultValue);
    }

    /**
     * Get a route param
     *
     * @param string $paramName
     * @param string $defaultValue
     * @return string
     */
    protected function getRouteParam($paramName, $defaultValue = null)
    {
        return RouteParamUtility::getParam($paramName, $defaultValue);
    }

    /**
     * Get request
     *
     * @return \Zend\Http\PhpEnvironment\Request
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
     * @return \Zend\ServiceManager\ServiceManager
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
     * @param string $key
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

        return $this->getServiceLocator()->get('controllerPluginManager')->get('redirect')->toUrl($url);
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
     * @return \Zend\Mvc\Controller\Plugin\FlashMessenger
     */
    protected function getFlashMessenger()
    {
        return $this->getServiceLocator()->
                get('controllerPluginManager')->get('flashMessenger');
    }

    /**
     * Get widget setting model
     *
     * @return \Page\Model\PageWidgetSetting
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
     * @param string $name
     * @return string|array|boolean
     */
    protected function getWidgetSetting($name)
    {
        $currentLanguage = LocalizationService::getCurrentLocalization()['language'];

        return  $this->getWidgetSettingModel()->
                getWidgetSetting($this->pageId, $this->widgetConnectionId, $name, $currentLanguage);
    }

    /**
     * Set current page id
     *
     * @param integer $pageId
     * @return \Page\View\Widget\IPageWidget
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
     * @return \Page\View\Widget\IPageWidget
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
     * @return \Page\View\Widget\IPageWidget
     */
    public function setWidgetPosition($position) 
    {
        $this->widgetPosition = $position;

        return $this;
    }

    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {}

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
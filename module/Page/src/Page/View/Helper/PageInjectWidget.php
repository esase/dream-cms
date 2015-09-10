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
namespace Page\View\Helper;

use Application\Utility\ApplicationCache as CacheUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Acl\Model\AclBase as AclBaseModel;
use Page\Exception\PageException;
use Page\View\Widget\IPageWidget;
use User\Service\UserIdentity as UserIdentityService;
use Zend\View\Helper\AbstractHelper;

class PageInjectWidget extends AbstractHelper
{
    /**
     * Redirect flag
     *
     * @var boolean
     */
    protected static $widgetRedirected = false;

    /**
     * Widgets
     *
     * @var array
     */
    protected $widgets = [];

    /**
     * Layout path
     *
     * @var string
     */
    protected $layoutPath = 'page/layout-widget/';

    /**
     * Dynamic cache instance
     *
     * @var \Zend\Cache\Storage\StorageInterface
     */
    protected $dynamicCache;

    /**
     * Request
     *
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $request;

    /**
     * Class constructor
     *
     * @param array $widgets
     */
    public function __construct(array $widgets = [])
    {
        $this->widgets = $widgets;
        $this->dynamicCache = ServiceLocatorService::getServiceLocator()->get('Application\Cache\Dynamic');
        $this->request = ServiceLocatorService::getServiceLocator()->get('Request');
    }

    /**
     * Call widget
     *
     * @param string $position
     * @param integer $pageId
     * @param integer $userRole
     * @param array $widgetInfo
     * @param boolean $useLayout
     * @throws \Page\Exception\PageException
     * @return string|boolean
     */
    protected function callWidget($position, $pageId, $userRole, array $widgetInfo, $useLayout = true)
    {
        // don't call any widgets
        if (true === self::$widgetRedirected) {
            return false;
        }

        // check a widget visibility
        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            if (!empty($widgetInfo['hidden']) && in_array($userRole, $widgetInfo['hidden'])) {
                return false;
            }
        }

        // call the widget
        $widget = $this->getView()->{$widgetInfo['widget_name']}();

        // check the widget
        if (!$widget instanceof IPageWidget) {
            throw new PageException(sprintf($widgetInfo['widget_name'] . ' must be an object implementing IPageWidget'));
        }

        // init the widget
        $widget->setPageId($pageId)
            ->setWidgetPosition($position)
            ->setWidgetConnectionId($widgetInfo['widget_connection_id']);

        $widgetCacheName = null;

        if ((int) $widgetInfo['widget_cache_ttl']) {
            // generate a cache name
            $widgetCacheName = CacheUtility::getCacheName($widgetInfo['widget_name'], [
                $widgetInfo['widget_connection_id']
            ]);

            // check the widget data in a cache
            if (null !== ($cachedWidgetData = $this->dynamicCache->getItem($widgetCacheName))) {
                // check a local widget lifetime
                if ($cachedWidgetData['widget_expire'] >= time()) {

                    // include widget's css and js files
                    if (false !== $cachedWidgetData['widget_content'] && !$this->request->isXmlHttpRequest()) {
                        $widget->includeJsCssFiles();
                    }

                    return $cachedWidgetData['widget_content'];
                }

                // clear cache
                $this->dynamicCache->removeItem($widgetCacheName);
            }
        }

        if (false !== ($widgetContent = $widget->getContent())) {
            self::$widgetRedirected = $widget->isWidgetRedirected();

            // include widget's css and js files
            if (!$this->request->isXmlHttpRequest()) {
                $widget->includeJsCssFiles();
            }

            // add the widget's layout
            if ($useLayout) {
                if (!empty($widgetInfo['widget_layout'])) {
                    $widgetContent = $this->getView()->partial($this->layoutPath . $widgetInfo['widget_layout'], [
                        'title' => $this->getView()->pageWidgetTitle($widgetInfo),
                        'content' => $widgetContent
                    ]);
                }
                else {
                    $widgetContent = $this->getView()->partial($this->layoutPath . 'default', [
                        'title' => $this->getView()->pageWidgetTitle($widgetInfo),
                        'content' => $widgetContent
                    ]);
                }
            }
        }

        // cache the widget data
        if ($widgetCacheName) {
            $this->dynamicCache->setItem($widgetCacheName, [
                'widget_content' => $widgetContent,
                'widget_expire'  => time() + $widgetInfo['widget_cache_ttl']
            ]);
        }

        return $widgetContent;
    }

    /**
     * Inject widget
     *
     * @param string $position
     * @param integer $pageId
     * @param integer $widgetConnectionId
     * @return string
     */
    public function __invoke($position, $pageId = null, $widgetConnectionId = null)
    {
        $result = null;
        $userRole = UserIdentityService::getCurrentUserIdentity()['role'];

        // get only a specific widget info
        if ($widgetConnectionId) {
            // search the widget on the specific page
            $widget = $pageId && !empty($this->widgets[$pageId][$position][$widgetConnectionId])
                ? $this->widgets[$pageId][$position][$widgetConnectionId]
                : null;

            // search the widget on non specific page
            if (!$widget) {
                $widget = !empty($this->widgets[''][$position][$widgetConnectionId])
                    ? $this->widgets[''][$position][$widgetConnectionId]
                    : null;
            }

            if ($widget) {
                $result = $this->callWidget($position, $pageId, $userRole, $widget, false);
            }
        }
        else {
            // get a page and position specific widgets
            if (!empty($this->widgets[$pageId][$position])) {
                foreach ($this->widgets[$pageId][$position] as $widgetInfo) {
                    if (false !== ($widgetCallResult = $this->callWidget($position, $pageId, $userRole, $widgetInfo))) {
                        $result .= $widgetCallResult;
                    }
                }
            }
        }

        return $result;
    }
}
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
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Application\Utility\ApplicationCache as CacheUtility;
use Application\Event\ApplicationEvent;

class ApplicationSettingAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Cache static
     */
    const CACHE_STATIC = 'static';

    /**
     * Cache dynamic
     */
    const CACHE_DYNAMIC = 'dynamic';

    /**
     * Cache config
     */
    const CACHE_CONFIG = 'config';

    /**
     * Cache js
     */
    const CACHE_JS = 'js';

    /**
     * Cache css
     */
    const CACHE_CSS = 'css';

    /**
     * Administration
     */
    public function indexAction()
    {
        $this->clearCaches();

        return new ViewModel([
            'settings_form' => parent::settingsForm('application', 'settings-administration', 'index')
        ]);
    }

    /**
     * Clear caches
     * 
     * @return void
     */
    protected function clearCaches()
    {
        // remember settings before changes 
        $jsCache = $this->applicationSetting('application_js_cache');
        $jsCacheGzip = $this->applicationSetting('application_js_cache_gzip');

        $cssCache = $this->applicationSetting('application_css_cache');
        $cssCacheGzip = $this->applicationSetting('application_css_cache_gzip');

        // clear js and css cache if needed
        $eventManager = ApplicationEvent::getEventManager();
        $eventManager->attach(ApplicationEvent::CHANGE_SETTINGS, function ()
                use ($jsCache, $jsCacheGzip, $cssCache, $cssCacheGzip) {

            // get post values
            $post = $this->getRequest()->getPost();

            // clear js cache
            if ($jsCache <> $post['application_js_cache'] 
                    || $jsCacheGzip <> $post['application_js_cache_gzip']) {

                if (true === ($clearResult = CacheUtility::clearJsCache())) {
                    ApplicationEvent::fireClearCacheEvent(self::CACHE_JS);
                }
            }

            // clear css cache
            if ($cssCache <> $post['application_css_cache'] 
                    || $cssCacheGzip <> $post['application_css_cache_gzip']) {

                if (true === ($clearResult = CacheUtility::clearCssCache())) {
                    ApplicationEvent::fireClearCacheEvent(self::CACHE_CSS);
                }
            }

            // clear the dynamic cache
            if (true === ($clearResult = CacheUtility::clearDynamicCache())) {
                ApplicationEvent::fireClearCacheEvent(self::CACHE_DYNAMIC);
            }
        });
    }

    /**
     * Clear cache
     */
    public function clearCacheAction()
    {
        // get a cache form
        $cacheForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationClearCache');

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $cacheForm->getForm()->setData($request->getPost(), false);

            // check the form validation
            if ($cacheForm->getForm()->isValid()) {
                if (null != ($caches = $cacheForm->getForm()->getData()['cache'])) {
                    // clear selected caches
                    foreach ($caches as $cache) {
                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission())) {
                            return $result;
                        }

                        $clearResult = false;

                        switch ($cache) {
                            case self::CACHE_STATIC :
                                $clearResult = CacheUtility::clearStaticCache();
                                break;

                            case self::CACHE_DYNAMIC :
                                $clearResult = CacheUtility::clearDynamicCache();
                                break;

                            case self::CACHE_CONFIG :
                                $clearResult = CacheUtility::clearConfigCache();
                                break;

                            case self::CACHE_JS :
                                $clearResult = CacheUtility::clearJsCache();
                                break;

                            case self::CACHE_CSS :
                                $clearResult = CacheUtility::clearCssCache();
                                break;
                        }

                        if (false === $clearResult) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(sprintf($this->getTranslator()->translate('Error clearing caches'), $cache));

                            break;
                        }
                    }

                    if (true === $clearResult) {
                        ApplicationEvent::fireClearCacheEvent($cache);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Selected caches have been cleared'));
                    }

                    return $this->redirectTo('settings-administration', 'clear-cache');
                }
            }
        }

        return new ViewModel([
            'cache_form' => $cacheForm->getForm()
        ]);
    }
}
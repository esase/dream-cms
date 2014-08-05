<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Application\Utility\Cache as CacheUtility;
use Application\Event\Event as ApplicationEvent;

class SettingAdministrationController extends AbstractAdministrationController
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
        $this->clearJsCssCache();

        return new ViewModel(array(
            'settingsForm' => parent::settingsForm('administration', 'settings-administration', 'index')
        ));
    }

    /**
     * Clear css and js caches
     * 
     * @return void
     */
    protected function clearJsCssCache()
    {
        // remember settings before changes 
        $jsCache = $this->getSetting('application_js_cache');
        $jsCacheGzip = $this->getSetting('application_js_cache_gzip');

        $cssCache = $this->getSetting('application_css_cache');
        $cssCacheGzip = $this->getSetting('application_css_cache_gzip');

        // clear js and css cache if needed
        $eventManager = ApplicationEvent::getEventManager();
        $eventManager->attach(ApplicationEvent::CHANGE_SETTINGS, 
                function ($e) use ($jsCache, $jsCacheGzip, $cssCache, $cssCacheGzip) {

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
            ->getInstance('Application\Form\ClearCache');

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
                        if (true !== ($result = $this->checkPermission())) {
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

        return new ViewModel(array(
            'cacheForm' => $cacheForm->getForm()
        ));
    }
}
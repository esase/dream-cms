<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Application\Utility\Cache as CacheUtility;
use Application\Event\Event as ApplicationEvent;
use User\Service\Service as UserService;

class SettingAdministrationController extends AbstractBaseController
{
    /**
     * Administration
     */
    public function indexAction()
    {
        // remember some settings before change 
        $jsCache = $this->getSetting('application_js_cache');
        $jsCacheGzip = $this->getSetting('application_js_cache_gzip');

        $cssCache = $this->getSetting('application_css_cache');
        $cssCacheGzip = $this->getSetting('application_css_cache_gzip');

        // clear js and css cache
        $eventManager = ApplicationEvent::getEventManager();
        $eventManager->attach(ApplicationEvent::CHANGE_SETTINGS, function ($e)
                use ($jsCache, $jsCacheGzip, $cssCache, $cssCacheGzip) {

            // get post values
            $post = $this->getRequest()->getPost();

            // clear js cache
            if ($jsCache <> $post['application_js_cache'] ||
                    $jsCacheGzip <> $post['application_js_cache_gzip']) {

                CacheUtility::clearJsCache();
            }

            // clear css cache
            if ($cssCache <> $post['application_css_cache'] ||
                    $cssCacheGzip <> $post['application_css_cache_gzip']) {

                CacheUtility::clearCssCache();
            }
        });

        return new ViewModel(array(
            'settingsForm' => parent::settingsForm('application', 'settings-administration', 'index')
        ));
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
                    // clear caches
                    foreach ($caches as $cache) {
                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->checkPermission())) {
                            return $result;
                        }

                        switch ($cache) {
                            case 'static' :
                                $clearResult = CacheUtility::clearStaticCache();
                                break;
                            case 'dynamic' :
                                $clearResult =  CacheUtility::clearDynamicCache();
                                break;
                            case 'config' :
                                $clearResult =  CacheUtility::clearConfigCache();
                                break;
                            case 'js' :
                                $clearResult = CacheUtility::clearJsCache();
                                break;
                            case 'css' :
                                $clearResult = CacheUtility::clearCssCache();
                                break;
                        }

                        if (false === $clearResult) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(sprintf($this->getTranslator()->translate('Error clearing caches'), $cache));

                            break;
                        }

                        // fire the clear cache event
                        ApplicationEvent::fireClearCacheEvent($cache);
                    }

                    if (true === $clearResult) {
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

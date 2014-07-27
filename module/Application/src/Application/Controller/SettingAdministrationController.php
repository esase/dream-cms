<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Application\Event\Event as ApplicationEvent;
use Application\Model\CacheAdministration as CacheAdministrationModel;

class SettingAdministrationController extends AbstractAdministrationController
{
    /**
     * Cache model instance
     * @var object  
     */
    protected $cacheModel;

    /**
     * Get cache model
     */
    protected function getCacheModel()
    {
        if (!$this->cacheModel) {
            $this->cacheModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\CacheAdministration');
        }

        return $this->cacheModel;
    }

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

                $this->getCacheModel()->clearCache(CacheAdministrationModel::CACHE_JS);
            }

            // clear css cache
            if ($cssCache <> $post['application_css_cache'] 
                    || $cssCacheGzip <> $post['application_css_cache_gzip']) {

                $this->getCacheModel()->clearCache(CacheAdministrationModel::CACHE_CSS);
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

                        // clearing specific cache 
                        $clearResult = $this->getCacheModel()->clearCache($cache);

                        if (false === $clearResult) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(sprintf($this->getTranslator()->translate('Error clearing caches'), $cache));

                            break;
                        }
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
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
use Users\Service\Service as UsersService;

class SettingsAdministrationController extends AbstractBaseController
{
    /**
     * Administration
     */
    public function indexAction()
    {
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
                    // event's description
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - Cache cleared by guest'
                        : 'Event - Cache cleared by user';

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

                        // fire the event
                        $eventDescParams = UsersService::isGuest()
                            ? array($cache)
                            : array(UsersService::getCurrentUserIdentity()->nick_name, $cache);

                        ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_CLEAR_CACHE,
                                $cache, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
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

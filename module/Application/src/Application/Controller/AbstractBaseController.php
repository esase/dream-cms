<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use User\Service\Service as UserService;
use Application\Event\Event as ApplicationEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\View\Model\ViewModel;
use Zend\Http\Response;

abstract class AbstractBaseController extends AbstractActionController
{
    /**
     * Translator
     * @var object  
     */
    protected $translator;

    /**
     * Order by value
     * @var string
     */
    protected $orderBy = null;

    /**
     * Order type value
     * @var string
     */
    protected $orderType = null;

    /**
     * Per page value
     * @var integer
     */
    protected $perPage = null;

    /**
     * Page value
     * @var integer
     */
    protected $page = null;

    /**
     * Slug
     * @var string
     */
    protected $slug = null;

    /**
     * Extra
     * @var string
     */
    protected $extra = null;

    /**
     * Set event manager
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;

        // check only ajax based actions
        $events->attach('dispatch', function ($e) use ($controller) {
            if ($e->getResponse()->getStatusCode() == Response::STATUS_CODE_200 && 
                    substr($controller->params('action'), 0, 4) == 'ajax' && !$e->getRequest()->isXmlHttpRequest()) {

                $controller->notFoundAction();
            }
        }, 99);

        // disable current layout if received request is an ajax request
        $events->attach('dispatch', function ($e) use ($controller) {
            $result = $e->getResult();
            if ($result instanceof ViewModel && 
                    $e->getResponse()->getStatusCode() == Response::STATUS_CODE_200) {

                $result->setTerminal($e->getRequest()->isXmlHttpRequest());
            }
        });
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        if ($this->slug === null) {
            $this->slug = $this->params()->fromRoute('slug', -1);
        }

        return $this->slug; 
    }

    /**
     * Get extra
     *
     * @return string
     */
    public function getExtra()
    {
        if ($this->extra === null) {
            $this->extra = $this->params()->fromRoute('extra');
        }

        return $this->extra; 
    }

    /**
     * Get page value
     *
     * @return integer
     */
    public function getPage()
    {
        if ($this->page === null) {
            $this->page = $this->params()->fromRoute('page', 1);
        }

        return $this->page; 
    }

    /**
     * Get order by value
     *
     * @return string
     */
    public function getOrderBy()
    {
        if ($this->orderBy === null) {
            $this->orderBy = $this->params()->fromRoute('order_by');
        }

        return $this->orderBy;
    }

    /**
     * Redirect to
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     * @param boolean $useReferer
     * @param array $queries
     * @param string $route
     * @return object
     */
    protected function redirectTo($controller = null, $action = null, array $params = array(), $useReferer = false, array $queries = array(), $route = 'application')
    {
        $request = $this->getRequest();

        // check the referer
        if ($useReferer && null != ($referer = $request->getHeader('Referer'))) {
            return $this->redirect()->toUrl($referer->uri());
        }

        $urlParams = $params
            ? array_merge(array('controller' => $controller, 'action' => $action), $params)
            : array('controller' => $controller, 'action' => $action);

        return $this->redirect()->toRoute($route, $urlParams, array('query' => $queries)); 
    }

    /**
     * Get order type
     *
     * @return string
     */
    public function getOrderType()
    {
        if ($this->orderType === null) {
            $this->orderType = $this->params()->fromRoute('order_type');
        }

        return $this->orderType;
    }

    /**
     * Get per page value
     *
     * @return string
     */
    public function getPerPage()
    {
        if ($this->perPage === null) {
            $this->perPage  = $this->params()->fromRoute('per_page');
        }

        return $this->perPage; 
    }

    /**
     * Get translation
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->getServiceLocator()->get('Translator');
        }

        return $this->translator;
    }

    /**
     * Generate settings form
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return object
     */
    protected function settingsForm($module, $controller, $action)
    {
        $currentlanguage = UserService::getCurrentLocalization()['language'];

        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\Setting');

        // get settings list
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\SettingAdministration');

        if (false !== ($settingsList = $settings->getSettingsList($module, $currentlanguage))) {
            $settingsForm->addFormElements($settingsList);
            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // fill the form with received values
                $settingsForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($settingsForm->getForm()->isValid()) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $settingsForm->getForm();
                    }

                    if (true === ($result = $settings->
                            saveSettings($settingsList, $settingsForm->getForm()->getData(), $currentlanguage))) {

                        // fire the change settings event
                        ApplicationEvent::fireChangeSettingsEvent($module);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));
                    }

                    $this->redirectTo($controller, $action);
                }
            }
        }

        return $settingsForm->getForm();
    }
}

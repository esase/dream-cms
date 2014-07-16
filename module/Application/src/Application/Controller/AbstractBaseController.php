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
     * Controller
     * @var string
     */
    protected $controller = null;

    /**
     * Action
     * @var string
     */
    protected $action = null;

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
     * Check the current user permission.
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @param boolean $showAccessDenied
     * @return boolean
     */
    public function checkPermission($resource = null, $increaseActions = true, $showAccessDenied = true)
    {
        // get ACL resource name
        $resource = !$resource
            ? $this->getController() . ' ' . $this->getAction()
            : $resource;

        // check the permission
        if (false === ($result = 
                UserService::checkPermission($resource, $increaseActions)) && $showAccessDenied) {

            // redirect to a forbidden page
            $this->showErrorPage();
        }

        return $result;
    }

    /**
     * Check a user authorization
     *
     * @param string $explanation
     * @return string|boolean
     */
    public function isAutorized($explanation = null)
    {
        if ($this->isGuest()) {
            $this->flashMessenger()
                ->setNamespace('error')
                ->addMessage($this->getTranslator()->translate(($explanation ? $explanation : 'You must be logged before view this page')));

            $backUrl = $this->url()->fromRoute('application', array(
                'controller' => $this->getController(), 
                'action' => $this->getAction(), 
                'slug' => $this->getSlug(false)
            ));

            return $this->redirectTo('user', 'login', array(), false, array('back' => $backUrl));
        }

        return true;
    }

    /**
     * Show an error page
     *
     * @param string $action
     * @return string
     */
    public function showErrorPage($action = 'forbidden')
    {
        return $this->redirectTo('error', $action);
    }

    /**
     * Is guest or not?.
     *
     * @return boolean
     */
    public function isGuest()
    {
        return UserService::isGuest();
    }

    /**
     * Get controller
     *
     * @return string
     */
    public function getController()
    {
        if ($this->controller === null) {
            $this->controller = $this->params()->fromRoute('controller');
        }

        return $this->controller; 
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        if ($this->action === null) {
            $this->action = $this->params()->fromRoute('action');
        }

        return $this->action; 
    }

    /**
     * Get slug
     *
     * @param boolean $defaultValue
     * @return string
     */
    public function getSlug($defaultValue = true)
    {
        if ($this->slug === null) {
            $this->slug = $this->params()->fromRoute('slug', ($defaultValue ? -1 : null));
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
     * @param string $default
     * @return string
     */
    public function getOrderBy($default = '')
    {
        if ($this->orderBy === null) {
            $this->orderBy = $this->params()->fromRoute('order_by');

            // set a default value
            if (!$this->orderBy && $default) {
                $this->orderBy = $default;
            }
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
     * @return string
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
     * @param string $default
     * @return string
     */
    public function getOrderType($default = '')
    {
        if ($this->orderType === null) {
            $this->orderType = $this->params()->fromRoute('order_type');

            // set a default value
            if (!$this->orderType && $default) {
                $this->orderType = $default;
            }
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

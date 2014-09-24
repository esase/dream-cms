<?php
namespace Application\Controller;

use User\Service\UserIdentity as UserIdentityService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\EventManager\EventManagerInterface;
use Zend\View\Model\ViewModel;
use Zend\Http\Response;

abstract class ApplicationAbstractBaseController extends AbstractActionController
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
     * Page name
     * @var string
     */
    protected $pageName = null;

    /**
     * Slug
     * @var string
     */
    protected $slug = null;

    /**
     * Language
     * @var string
     */
    protected $language = null;

    /**
     * Category
     * @var string
     */
    protected $category = null;

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
        return UserIdentityService::isGuest();
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
     * Get page name
     *
     * @param boolean $defaultValue
     * @return string
     */
    public function getPageName($defaultValue = true)
    {
        if ($this->pageName === null) {
            $this->pageName = $this->params()->fromRoute('page_name', ($defaultValue ? 'home' : null));
        }

        return $this->pageName; 
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
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        if ($this->language === null) {
            $this->language = $this->params()->fromRoute('language');
        }

        return $this->language; 
    }

    /**
     * Get category
     *
     * @return string
     */
    public function geCategory()
    {
        if ($this->category === null) {
            $this->category = $this->params()->fromRoute('category');
        }

        return $this->category; 
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
    protected function redirectTo($controller = null, $action = null, array $params = [], $useReferer = false, array $queries = [], $route = 'application/page')
    {
        $request = $this->getRequest();

        // check the referer
        if ($useReferer && null != ($referer = $request->getHeader('Referer'))) {
            return $this->redirect()->toUrl($referer->uri());
        }

        $urlParams = $params
            ? array_merge(['controller' => $controller, 'action' => $action], $params)
            : ['controller' => $controller, 'action' => $action];

        return $this->redirect()->toRoute($route, $urlParams, ['query' => $queries]); 
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
}
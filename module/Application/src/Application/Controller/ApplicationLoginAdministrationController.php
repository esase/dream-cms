<?php
namespace Application\Controller;

use Acl\Service\Acl as AclService;
use User\Service\UserIdentity as UserIdentityService;
use User\Utility\UserAuthenticate as UserAuthenticateUtility;
use Zend\View\Model\ViewModel;
use Zend\Http\Response;

class ApplicationLoginAdministrationController extends ApplicationAbstractBaseController
{
    /**
     * Layout name
     */
    protected $layout = 'layout/blank';

    /**
     * Admin menu model instance
     * @var object  
     */
    protected $adminMenuModel;

    /**
     * Get admin menu model
     */
    protected function getAdminMenuModel()
    {
        if (!$this->adminMenuModel) {
            $this->adminMenuModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\ApplicationAdminMenu');
        }

        return $this->adminMenuModel;
    }

    /**
     * Index page
     */
    public function indexAction()
    {
        if (!UserIdentityService::isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $this->layout($this->layout);

        $loginForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\UserLogin');

        if ($this->getRequest()->isPost()) {
            // fill form with received values
            $loginForm->getForm()->setData($this->getRequest()->getPost());

            if ($loginForm->getForm()->isValid()) {
                $userName = $this->getRequest()->getPost('nickname');
                $password = $this->getRequest()->getPost('password');

                // check an authentication
                $authErrors = [];
                $result = UserAuthenticateUtility::
                        isAuthenticateDataValid($userName, $password, $authErrors);

                if (false === $result) {
                    $this->flashMessenger()->setNamespace('error');

                    // add auth error messages
                    foreach ($authErrors as $message) {
                        $this->flashMessenger()->addMessage($this->getTranslator()->translate($message));
                    }

                    return $this->reloadPage();
                }

                $rememberMe = null != ($remember = $this->getRequest()->getPost('remember')) 
                    ? true 
                    : false;

                // login a user
                UserAuthenticateUtility::loginUser($result['user_id'], $result['nick_name'], $rememberMe);

                // make a redirect
                if (null !== ($backUrl = $this->getRequest()->getQuery('back_url', null))) {
                    return $this->redirect()->toUrl($backUrl);
                }

                // search a first allowed admin page
                $adminMenu = $this->getAdminMenuModel()->getMenu();
                foreach ($adminMenu as $menuItems) {
                    foreach ($menuItems['items'] as $item) {
                        if (AclService::checkPermission($item['controller'] . ' ' . $item['action'], false)) {
                            return $this->redirectTo($item['controller'], $item['action']);
                        }
                    }
                }

                // redirect to the public home page
                $this->flashMessenger()->setNamespace('error');
                $this->flashMessenger()->addMessage($this->
                        getTranslator()->translate('There are no admin pages allowed for you!'));

                return $this->redirectTo('page', 'index', [], false, [], 'page');
            }
        }

        return new ViewModel([
            'loginForm' => $loginForm->getForm()
        ]);
    }
}
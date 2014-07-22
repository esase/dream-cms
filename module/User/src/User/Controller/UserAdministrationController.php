<?php
namespace User\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAdministrationController;
use Application\Model\Acl as AclBaseModel;
use User\Service\Service as UserService;
use User\Event\Event as UserEvent;
use User\Model\UserAdministration as UserAdministrationModel;

class UserAdministrationController extends AbstractAdministrationController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Acl model instance
     * @var object  
     */
    protected $aclModel;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserAdministration');
        }

        return $this->model;
    }

    /**
     * Get acl model
     */
    protected function getAclModel()
    {
        if (!$this->aclModel) {
            $this->aclModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\AclAdministration');
        }

        return $this->aclModel;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel(array(
            'settingsForm' => parent::settingsForm('user', 'users-administration', 'settings')
        ));
    }

    /**
     * Acl resource's settings
     */
    public function aclResourceSettingsAction()
    {
        
            
         $eventManager = \Application\Event\Event::getEventManager();
        $eventManager->attach(\Application\Event\Event::EDIT_ACL_RESOURCE_SETTINGS, function ($e) {
            
            echo vsprintf($this->getServiceLocator()->get('Translator')->translate($e->getParam('description')), $e->getParam('description_params'));
            exit;
        });
        
        
        // get the user info
        if (null == ($user = $this->getModel()->getUserInfo($this->params()->
                fromQuery('user', -1))) || $user['role'] == AclBaseModel::DEFAULT_ROLE_ADMIN) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get resource's settings info
        if (null == ($settings =
                $this->getAclModel()->getResourceSettings($this->getSlug(), $user['user_id']))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an acl resource's settings form
        $aclResourceSettingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclResourceSetting')
            ->setActionsLimit($settings['actions_limit'])
            ->setActionsReset($settings['actions_reset'])
            ->setDateStart($settings['date_start'])
            ->setDateEnd($settings['date_end'])
            ->showActionCleanCounter();

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclResourceSettingsForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclResourceSettingsForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit settings
                $cleanCounter = $this->params()->fromPost('clean_counter') ? true : false;
                if (true == ($result = $this->getAclModel()->editResourceSettings($settings['connection'], 
                        $settings['resource'], $settings['role'], $aclResourceSettingsForm->getForm()->getData(), $user['user_id'], $cleanCounter))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Resource\'s settings have been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'acl-resource-settings', array(
                    'slug' => $settings['connection']
                ), false, array('user' => $user['user_id']));
            }
        }

        return new ViewModel(array(
            'user' => $user,
            'resourceSettings' => $settings,
            'aclResourceSettingsForm' => $aclResourceSettingsForm->getForm()
        ));
    }

    /**
     * Browse the user's allowed ACL resources
     */
    public function browseAclResourcesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        // get the user info
        if (null == ($user = $this->getModel()->getUserInfo($this->
                getSlug())) || $user['role'] == AclBaseModel::DEFAULT_ROLE_ADMIN) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // we need only allowed ACL resources
        $filters = array(
            'status' => AclBaseModel::ACTION_ALLOWED
        );

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclResourceFilter')
            ->setModel($this->getAclModel())
            ->hideStatusFilter(true);

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = array_merge($filterForm->getForm()->getData(), $filters);
        }

        // get data
        $paginator = $this->getAclModel()->getResources($user['role'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'slug' => $user['user_id'],
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'user' => $user
        ));
    }

    /**
     * Edit the user's role
     */
    public function editRoleAction()
    {
        // get the user info
        if (null == ($user = $this->getModel()->getUserInfo($this->
                getSlug())) || $user['user_id'] == UserAdministrationModel::DEFAULT_USER_ID) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a role form
        $roleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\Role');

        // fill the form with default values
        $roleForm->getForm()->setData($user);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $roleForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($roleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // get the role name
                $roleName = UserService::getAclRoles()[$roleForm->getForm()->getData()['role']];

                if (true === ($result = $this->getModel()->
                        editUserRole($user['user_id'], $roleForm->getForm()->getData()['role'], $roleName, $user->getArrayCopy()))) {

                    // fire the edit user role event
                    UserEvent::fireEditRoleEvent($user, UserService::getAclRoles()[$roleForm->getForm()->getData()['role']]);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User\'s role has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'edit-role', array(
                    'slug' => $user['user_id']
                ));
            }
        }

        return new ViewModel(array(
            'roleForm' => $roleForm->getForm(),
            'user' => $user
        ));
    }

    /**
     * Edit the user
     */
    public function editUserAction()
    {
        // get the user info
        if (null == ($user = $this->getModel()->getUserInfo($this->getSlug()))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the user form
        $userForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\User')
            ->setModel($this->getModel())
            ->setUserId($user['user_id'])
            ->setUserAvatar($user['avatar']);
 
        // fill the form with default values
        $userForm->getForm()->setData($user);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $userForm->getForm()->setData($post, false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the user
                $status = $user['status'] == UserAdministrationModel::STATUS_APPROVED ?:false;
                $deleteAvatar = (int) $this->getRequest()->getPost('avatar_delete')
                    ? true
                    : false;

                if (true === ($result = $this->getModel()->editUser($user, $userForm->
                        getForm()->getData(), $status, $this->params()->fromFiles('avatar'), $deleteAvatar))) {

                    // fire the edit user event
                    UserEvent::fireUserEditEvent($user['user_id']);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'edit-user', array(
                    'slug' => $user['user_id']
                ));
            }
        }

        return new ViewModel(array(
            'userForm' => $userForm->getForm(),
            'user' => $user
        ));
    }

    /**
     * Add a new user
     */
    public function addUserAction()
    {
        // get an user form
        $userForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\User')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $userForm->getForm()->setData($post, false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // add a new user
                $result = $this->getModel()->
                        addUser($userForm->getForm()->getData(), true, $this->params()->fromFiles('avatar'));

                if (is_numeric($result)) {
                    // fire the add user event
                    UserEvent::fireUserAddEvent($result);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'add-user');
            }
        }

        return new ViewModel(array(
            'userForm' => $userForm->getForm()
        ));
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('users-administration', 'list');
    }

    /**
     * Delete selected users
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($usersIds = $request->getPost('users', null))) {
                // delete selected users
                foreach ($usersIds as $userId) {
                    // default user should not be deleted
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { 

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // fire the delete user event
                    UserEvent::fireUserDeleteEvent($userId, $userInfo);

                    // delete the user
                    if (true !== ($deleteResult = $this->getModel()->deleteUser($userInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected users have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('users-administration', 'list', array(), true);
    }

    /**
     * Approve selected users
     */
    public function approveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($usersIds = $request->getPost('users', null))) {
                // approve selected users
                $approveResult = false;
                foreach ($usersIds as $userId) {
                    // default user should not be touched
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { 

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // approve the user
                    if (true !== ($approveResult = $this->getModel()->setUserStatus($userId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($approveResult));

                        break;
                    }

                    // fire the approve user event
                    UserEvent::fireUserApproveEvent($userId, $userInfo);
                }

                if (true === $approveResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected users have been approved'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('users-administration', 'list', array(), true);
    }

    /**
     * Disapprove selected users
     */
    public function disapproveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($usersIds = $request->getPost('users', null))) {
                // disapprove selected users
                foreach ($usersIds as $userId) {
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { // default user should not be touched

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // disapprove the user
                    if (true !== ($disapproveResult = $this->getModel()->setUserStatus($userId, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($disapproveResult));

                        break;
                    }

                    // fire the disapprove user event
                    UserEvent::fireUserDisapproveEvent($userId, $userInfo);
                }

                if (true === $disapproveResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected users have been disapproved'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('users-administration', 'list', array(), true);
    }

    /**
     * User list 
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        $filters = array();

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\UserFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getUsers($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }
}
<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Users\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Application\Model\Acl as AclBase;
use Users\Service\Service as UsersService;
use Users\Event\Event as UsersEvent;
use Application\Utility\EmailNotification;

class UsersAdministrationController extends AbstractBaseController
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
                ->getInstance('Users\Model\UsersAdministration');
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
            'settingsForm' => parent::settingsForm('users', 'users-administration', 'settings')
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
            ->getInstance('Users\Form\User')
            ->setModel($this->getModel())
            ->setUserId($user['user_id']);
 
        // fill the form with default values
        $userForm->getForm()->setData($user);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $userForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the user
                if (true == ($result = $this->
                        getModel()->editUser($user['user_id'], $userForm->getForm()->getData()))) {

                    // event's description
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - User edited by guest'
                        : 'Event - User edited by user';

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($user['user_id'])
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $user['user_id']);

                    UsersEvent::fireEvent(UsersEvent::USER_EDIT,
                            $user['user_id'], UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($result);
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
            ->getInstance('Users\Form\User')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $userForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // add a new user
                $result = $this->getModel()->addUser($userForm->getForm()->getData());

                if (is_numeric($result)) {
                    // event's description
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - User added by guest'
                        : 'Event - User added by user';

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($result)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $result);

                    UsersEvent::fireEvent(UsersEvent::USER_ADD,
                            $result, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($result);
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
                // event's description
                $eventDesc = UsersService::isGuest()
                    ? 'Event - User deleted by guest'
                    : 'Event - User deleted by user';

                // delete selected users
                foreach ($usersIds as $userId) {
                    // default user should not be deleted
                    if ($userId == AclBase::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { 

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($userId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $userId);

                    UsersEvent::fireEvent(UsersEvent::USER_DELETE,
                            $userId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    // delete the user
                    if (true !== ($deleteResult = $this->getModel()->deleteUser($userId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $deleteResult : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // send an email notification
                    if ((int) $this->getSetting('user_deleted_send')) {
                        $notificationLanguage = $userInfo['language']
                            ? $userInfo['language'] // we should use the user's language
                            : UsersService::getDefaultLocalization()['language'];
    
                        EmailNotification::sendNotification($userInfo['email'],
                                $this->getSetting('user_deleted_title', $notificationLanguage),
                                $this->getSetting('user_deleted_message', $notificationLanguage), array(
                                    'find' => array(
                                        'RealName'
                                    ),
                                    'replace' => array(
                                        $userInfo['nick_name']
                                    )
                                ));
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
                // event's description
                $eventDesc = UsersService::isGuest()
                    ? 'Event - User approved by guest'
                    : 'Event - User approved by user';

                // approve selected users
                foreach ($usersIds as $userId) {
                    // default user should not be touched
                    if ($userId == AclBase::DEFAULT_USER_ID ||
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
                            ->addMessage($approveResult);

                        break;
                    }

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($userId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $userId);

                    UsersEvent::fireEvent(UsersEvent::USER_APPROVE,
                            $userId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    // send an email notification
                    $notificationLanguage = $userInfo['language']
                        ? $userInfo['language'] // we should use the user's language
                        : UsersService::getDefaultLocalization()['language'];

                    EmailNotification::sendNotification($userInfo['email'],
                            $this->getSetting('user_approved_title', $notificationLanguage),
                            $this->getSetting('user_approved_message', $notificationLanguage), array(
                                'find' => array(
                                    'RealName',
                                    'Email'
                                ),
                                'replace' => array(
                                    $userInfo['nick_name'],
                                    $userInfo['email']
                                )
                            ));
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
                // event's description
                $eventDesc = UsersService::isGuest()
                    ? 'Event - User disapproved by guest'
                    : 'Event - User disapproved by user';

                // disapprove selected users
                foreach ($usersIds as $userId) {
                    if ($userId == AclBase::DEFAULT_USER_ID ||
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
                            ->addMessage($disapproveResult);

                        break;
                    }

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($userId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $userId);

                    UsersEvent::fireEvent(UsersEvent::USER_DISAPPROVE,
                            $userId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    // send an email notification
                    $notificationLanguage = $userInfo['language']
                        ? $userInfo['language'] // we should use the user's language
                        : UsersService::getDefaultLocalization()['language'];

                    EmailNotification::sendNotification($userInfo['email'],
                            $this->getSetting('user_disapproved_title', $notificationLanguage),
                            $this->getSetting('user_disapproved_message', $notificationLanguage), array(
                                'find' => array(
                                    'RealName',
                                    'Email'
                                ),
                                'replace' => array(
                                    $userInfo['nick_name'],
                                    $userInfo['email']
                                )
                            ));
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
     * Users list 
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
            ->getInstance('Users\Form\UsersFilter')
            ->setAclModel($this->getAclModel());

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

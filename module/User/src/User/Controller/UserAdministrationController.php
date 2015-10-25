<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace User\Controller;

use Application\Service\ApplicationTimeZone as TimeZoneService;
use Acl\Model\AclBase as AclBaseModel;
use Acl\Service\Acl as AclService;
use Application\Controller\ApplicationAbstractAdministrationController;
use User\Model\UserAdministration as UserAdministrationModel;
use Localization\Service\Localization as LocalizationService;
use Zend\View\Model\ViewModel;

class UserAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     *
     * @var \User\Model\UserAdministration
     */
    protected $model;

    /**
     * Acl model instance
     *
     * @var \Acl\Model\AclAdministration
     */
    protected $aclModel;

    /**
     * Get model
     *
     * @return \User\Model\UserAdministration
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
     *
     * @return \Acl\Model\AclAdministration
     */
    protected function getAclModel()
    {
        if (!$this->aclModel) {
            $this->aclModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Acl\Model\AclAdministration');
        }

        return $this->aclModel;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel([
            'settings_form' => parent::settingsForm('user', 'users-administration', 'settings')
        ]);
    }

    /**
     * Acl resource's settings
     */
    public function aclResourceSettingsAction()
    {
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
            ->getInstance('Acl\Form\AclResourceSetting')
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
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit settings
                $cleanCounter = $this->params()->fromPost('clean_counter') ? true : false;
                if (true === ($result = $this->getAclModel()->editResourceSettings($settings['connection'], 
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

                return $this->redirectTo('users-administration', 'acl-resource-settings', [
                    'slug' => $settings['connection']
                ], false, ['user' => $user['user_id']]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'user' => $user,
            'resource_settings' => $settings,
            'acl_form' => $aclResourceSettingsForm->getForm()
        ]);
    }

    /**
     * Browse the user's allowed ACL resources
     */
    public function browseAclResourcesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the user info
        if (null == ($user = $this->getModel()->getUserInfo($this->
                getSlug())) || $user['role'] == AclBaseModel::DEFAULT_ROLE_ADMIN) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // we need only allowed ACL resources
        $filters = [
            'status' => AclBaseModel::ACTION_ALLOWED
        ];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclResourceFilter')
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

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'slug' => $user['user_id'],
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'user' => $user
        ]);
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
            ->getInstance('User\Form\UserRole');

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
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // get the role name
                $roleName = AclService::getAclRoles()[$roleForm->getForm()->getData()['role']];

                if (true === ($result = $this->getModel()->
                        editUserRole($user['user_id'], $roleForm->getForm()->getData()['role'], $roleName, (array) $user))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User\'s role has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'edit-role', [
                    'slug' => $user['user_id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'role_form' => $roleForm->getForm(),
            'user' => $user
        ]);
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
            ->setTimeZones(TimeZoneService::getTimeZones())
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
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the user
                $status = $user['status'] == UserAdministrationModel::STATUS_APPROVED ?:false;
                $deleteAvatar = (int) $this->getRequest()->getPost('avatar_delete') ? true : false;

                if (true === ($result = $this->getModel()->editUser($user, $userForm->
                        getForm()->getData(), $status, $this->params()->fromFiles('avatar'), $deleteAvatar))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('User has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('users-administration', 'edit-user', [
                    'slug' => $user['user_id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'user_form' => $userForm->getForm(),
            'user' => $user
        ]);
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
            ->setModel($this->getModel())
            ->setTimeZones(TimeZoneService::getTimeZones());

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
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new user
                $result = $this->getModel()->addUser($userForm->getForm()->getData(), 
                        LocalizationService::getCurrentLocalization()['language'], true, $this->params()->fromFiles('avatar'));

                if (is_numeric($result)) {
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

        return new ViewModel([
            'user_form' => $userForm->getForm()
        ]);
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

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($usersIds = $request->getPost('users', null))) {
                // delete selected users
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($usersIds as $userId) {
                    // default user should not be deleted
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { 

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the user
                    if (true !== ($deleteResult = $this->getModel()->deleteUser($userInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected users have been deleted'
                        : 'The selected user has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('users-administration', 'list', [], true);
    }

    /**
     * Approve selected users
     */
    public function approveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($usersIds = $request->getPost('users', null))) {
                // approve selected users
                $approveResult = false;
                $approvedCount = 0;

                foreach ($usersIds as $userId) {
                    // default user should not be touched
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { 

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // approve the user
                    if (true !== ($approveResult = 
                            $this->getModel()->setUserStatus($userId, true, (array) $userInfo))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($approveResult));

                        break;
                    }

                    $approvedCount++;
                }

                if (true === $approveResult) {
                    $message = $approvedCount > 1
                        ? 'Selected users have been approved'
                        : 'The selected user has been approved';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('users-administration', 'list', [], true);
    }

    /**
     * Disapprove selected users
     */
    public function disapproveAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
            $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($usersIds = $request->getPost('users', null))) {
                // disapprove selected users
                $disapproveResult = false;
                $disapprovedCount = 0;

                foreach ($usersIds as $userId) {
                    if ($userId == UserAdministrationModel::DEFAULT_USER_ID ||
                                null == ($userInfo = $this->getModel()->getUserInfo($userId))) { // default user should not be touched

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // disapprove the user
                    if (true !== ($disapproveResult = 
                            $this->getModel()->setUserStatus($userId, false, (array) $userInfo))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($disapproveResult));

                        break;
                    }

                    $disapprovedCount++;
                }

                if (true === $disapproveResult) {
                    $message = $disapprovedCount > 1
                        ? 'Selected users have been disapproved'
                        : 'The selected user has been disapproved';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('users-administration', 'list', [], true);
    }

    /**
     * User list 
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

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

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }
}
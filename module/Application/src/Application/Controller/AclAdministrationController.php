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
use Users\Service\Service as UsersService;
use Application\Event\Event as ApplicationEvent;

class AclAdministrationController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\AclAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('acl-administration', 'list');
    }

    /**
     * Allow selected resources
     */
    public function allowResourcesAction()
    {
        // get the role info
        if (null == ($role = $this->
                getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($resourcesIds = $request->getPost('resources', null))) {
                // event's description
                $eventDesc = UsersService::isGuest()
                   ? 'Event - ACL resource allowed by guest'
                   : 'Event - ACL resource allowed by user';

                // allow recources
                foreach ($resourcesIds as $resourceId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // allow the resource
                    if (true !== ($allowResult = $this->getModel()->allowResource($role['id'],
                            $resourceId))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($allowResult));

                        break;
                    }

                    $eventDescParams = UsersService::isGuest()
                        ? array($resourceId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $resourceId);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_ALLOW_ACL_RESOURCE,
                            $resourceId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $allowResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected resources have been allowed'));                 
                }                
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'browse-resources', array(
            'slug' => $role['id']
        ), true);
    }

    /**
     * Disallow selected resources
     */
    public function disallowResourcesAction()
    {
        // get the role info
        if (null == ($role = $this->
                getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($resourcesIds = $request->getPost('resources', null))) {
                // event's description
                $eventDesc = UsersService::isGuest()
                    ? 'Event - ACL resource disallowed by guest'
                    : 'Event - ACL resource disallowed by user';

                // disallow recources
                foreach ($resourcesIds as $resourceId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // disallow the resource
                    if (true !== ($disallowResult = $this->getModel()->disallowResource($role['id'],
                            $resourceId))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($disallowResult));

                        break;
                    }

                    $eventDescParams = UsersService::isGuest()
                        ? array($resourceId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $resourceId);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_DISALLOW_ACL_RESOURCE,
                            $resourceId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $disallowResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected resources have been disallowed'));                    
                }
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'browse-resources', array(
            'slug' => $role['id']
        ), true);
    }

    /**
     * Delete selected roles
     */
    public function deleteRolesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($rolesIds = $request->getPost('roles', null))) {
                // event's description
                $eventDesc = UsersService::isGuest()
                    ? 'Event - ACL role deleted by guest'
                    : 'Event - ACL role deleteted by user';

                // delete selected roles
                foreach ($rolesIds as $roleId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // delete the role
                    if (true !== ($deleteResult = $this->getModel()->deleteRole($roleId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // fire the event
                    $eventDescParams = UsersService::isGuest()
                        ? array($roleId)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $roleId);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_DELETE_ACL_ROLE,
                            $roleId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected roles have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'list', array(), true);
    }

    /**
     * Edit a role action
     */
    public function editRoleAction()
    {
        // get the role info
        if (null == ($role = $this->
                getModel()->getRoleInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an acl role form
        $aclRoleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclRole');

        $aclRoleForm->getForm()->setData($role);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclRoleForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the role
                if (true == ($result = $this->
                        getModel()->editRole($role['id'], $aclRoleForm->getForm()->getData()))) {

                    // fire the event
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - ACL role edited by guest'
                        : 'Event - ACL role edited by user';

                    $eventDescParams = UsersService::isGuest()
                        ? array($role['id'])
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $role['id']);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_EDIT_ACL_ROLE,
                            $role['id'], UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'edit-role', array(
                    'slug' => $role['id']
                ));
            }
        }

        return new ViewModel(array(
            'role' => $role,
            'aclRoleForm' => $aclRoleForm->getForm()
        ));
    }

    /**
     * Add a new role action
     */
    public function addRoleAction()
    {
        // get an acl role form
        $aclRoleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclRole');

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclRoleForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // add a new role
                $result = $this->getModel()->addRole($aclRoleForm->getForm()->getData());

                if (is_numeric($result)) {
                    // fire the event
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - ACL role added by guest'
                        : 'Event - ACL role added by user';

                    $eventDescParams = UsersService::isGuest()
                        ? array($result)
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $result);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_ADD_ACL_ROLE,
                            $result, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'add-role');
            }
        }

        return new ViewModel(array(
            'aclRoleForm' => $aclRoleForm->getForm()
        ));
    }

    /**
     * Acl roles list 
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
            ->getInstance('Application\Form\AclRoleFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getRoles($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }

    /**
     * Acl browse resources
     */
    public function browseResourcesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        // get the role info
        if (null == ($role = $this->
                getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $filters = array();

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclResourceFilter');

        $filterForm->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getResources($role['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'slug' => $role['id'],
            'roleInfo' => $role,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ));
    }

    /**
     * Acl resource's settings
     */
    public function resourceSettingsAction()
    {
        // get resource's settings info
        if (null == ($resourceSettings =
                $this->getModel()->getResourceSettings($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an acl resource's settings form
        $aclResourceSettingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclResourceSettings');

        // fill the form with default values
        $aclResourceSettingsForm->setActionsLimit($resourceSettings['actions_limit'])
            ->setActionsReset($resourceSettings['actions_reset'])
            ->setDateStart($resourceSettings['date_start'])
            ->setDateEnd($resourceSettings['date_end']);

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
                if (true == ($result = $this->getModel()->
                        editResourceSettings($resourceSettings['connection'], $aclResourceSettingsForm->getForm()->getData()))) {

                    // fire the event
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - ACL resource settings edited by guest'
                        : 'Event - ACL resource settings edited by user';

                    $eventDescParams = UsersService::isGuest()
                        ? array($resourceSettings['role'], $resourceSettings['resource'])
                        : array(UsersService::getCurrentUserIdentity()->nick_name, $resourceSettings['role'], $resourceSettings['resource']);

                    ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_EDIT_ACL_RESOURCE_SETTINGS,
                            $resourceSettings['connection'], UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Resource\'s settings have been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'resource-settings', array(
                    'slug' => $resourceSettings['connection']
                ));
            }
        }

        return new ViewModel(array(
            'resourceSettings' => $resourceSettings,
            'aclResourceSettingsForm' => $aclResourceSettingsForm->getForm()
        ));
    }
}

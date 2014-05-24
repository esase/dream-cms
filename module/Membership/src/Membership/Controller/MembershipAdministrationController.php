<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Membership\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Membership\Event\Event as MembershipEvent;
use User\Service\Service as UserService;

class MembershipAdministrationController extends AbstractBaseController
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
                ->getInstance('Membership\Model\MembershipAdministration');
        }

        return $this->model;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel(array(
            'settingsForm' => parent::settingsForm('membership', 'memberships-administration', 'settings')
        ));
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('memberships-administration', 'list');
    }

    /**
     * List of membership levels
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
            ->getInstance('Membership\Form\MembershipFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getMembershipLevels($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel(array(
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
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
            ->getInstance('Membership\Form\AclRole');

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $aclRoleForm->getForm()->setData($post, false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // add a new role
                $result = $this->getModel()->addRole($aclRoleForm->
                        getForm()->getData(), $this->params()->fromFiles('image'));

                if (is_numeric($result)) {
                    // event's description
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Membership role added by guest'
                        : 'Event - Membership role added by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($result)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $result);

                    MembershipEvent::fireEvent(MembershipEvent::ADD_MEMBERSHIP_ROLE,
                            $result, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('memberships-administration', 'add-role');
            }
        }

        return new ViewModel(array(
            'aclRoleForm' => $aclRoleForm->getForm()
        ));
    }

    /**
     * Edit a role action
     */
    public function editRoleAction()
    {
        // get the role info
        if (null == ($role = $this->getModel()->getRoleInfo($this->getSlug()))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an acl role form
        $aclRoleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Membership\Form\AclRole')
            ->setImage($role['image']);

        $aclRoleForm->getForm()->setData($role);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $aclRoleForm->getForm()->setData($post, false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->checkPermission())) {
                    return $result;
                }

                // edit the role
                if (true == ($result = $this->getModel()->editRole($role, 
                        $aclRoleForm->getForm()->getData(), $this->params()->fromFiles('image')))) {

                    // fire the event
                    $eventDesc = UserService::isGuest()
                        ? 'Event - Membership role edited by guest'
                        : 'Event - Membership role edited by user';

                    $eventDescParams = UserService::isGuest()
                        ? array($role['id'])
                        : array(UserService::getCurrentUserIdentity()->nick_name, $role['id']);

                    MembershipEvent::fireEvent(MembershipEvent::EDIT_MEMBERSHIP_ROLE,
                            $role['id'], UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('memberships-administration', 'edit-role', array(
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
     * Delete selected membership roles
     */
    public function deleteRolesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($rolesIds = $request->getPost('roles', null))) {
                // event's description
                $eventDesc = UserService::isGuest()
                    ? 'Event - Membership role deleted by guest'
                    : 'Event - Membership role deleted by user';

                // delete selected membership roles
                foreach ($rolesIds as $roleId) {
                    // get the role info
                    if (null == ($roleInfo = $this->getModel()->getRoleInfo($roleId))) { 
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $result;
                    }

                    // delete the role
                    if (true !== ($deleteResult = $this->getModel()->deleteRole($roleInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // fire the event
                    $eventDescParams = UserService::isGuest()
                        ? array($roleId)
                        : array(UserService::getCurrentUserIdentity()->nick_name, $roleId);

                    MembershipEvent::fireEvent(MembershipEvent::DELETE_MEMBERSHIP_ROLE,
                            $roleId, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                }
            }
        }

        // redirect back
        return $this->redirectTo('memberships-administration', 'list', array(), true);
    }
}
<?php
namespace Membership\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Membership\Event\Event as MembershipEvent;

class MembershipAdministrationController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * User Model instance
     * @var object  
     */
    protected $userModel;

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
     * Get user model
     */
    protected function getUserModel()
    {
        if (!$this->userModel) {
            $this->userModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\Base');
        }

        return $this->userModel;
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
                    // fire the add membership role event
                    MembershipEvent::fireAddMembershipRoleEvent($result);

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
            ->setEditMode(true)
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

                    // fire the edit membership role event
                    MembershipEvent::fireEditMembershipRoleEvent($role['id']);

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
                // delete selected membership roles
                foreach ($rolesIds as $roleId) {
                    // get the role info, membership levels cannot be deleted  while they contain subscribers
                    if (null == ($roleInfo = $this->getModel()->getRoleInfo($roleId)) 
                            || $roleInfo['subscribers']) { 

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

                    // fire the delete membership role event
                    MembershipEvent::fireDeleteMembershipRoleEvent($roleId);
                }
            }
        }

        // redirect back
        return $this->redirectTo('memberships-administration', 'list', array(), true);
    }
}
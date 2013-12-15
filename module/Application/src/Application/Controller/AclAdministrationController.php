<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Users\Service\Service as UsersService;
use Application\Event\Event as ApplicationEvent;

class AclAdministrationController extends AbstractAdministrationController
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
        return $this->redirectTo('acl-administration', 'list', false);
    }

    /**
     * Delete selected roles
     */
    public function deleteRolesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($rolesIds = $request->getPost('roles', null))) {
                if (true === ($result = $this->getModel()->deleteRoles($rolesIds))) {
                    // fire the event
                    $eventDesc = UsersService::isGuest()
                        ? 'Event - ACL role deleted (guest)'
                        : 'Event - ACL role deleted (user)';

                    foreach ($rolesIds as $roleId) {
                        $eventDescParams = UsersService::isGuest()
                            ? array($roleId)
                            : array(UsersService::getCurrentUserIdentity()->nick_name, $roleId);

                        ApplicationEvent::fireEvent(ApplicationEvent::APPLICATION_DELETE_ACL_ROLE,
                                $roleId, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);
                    }

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected roles have been deleted'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage(($result ? $result : $this->getTranslator()->translate('Error occurred')));
                }
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'list');
    }

    /**
     * Acl roles list 
     */
    public function listAction()
    {
        $filters = array();

        // get filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\AclFilter');

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
}

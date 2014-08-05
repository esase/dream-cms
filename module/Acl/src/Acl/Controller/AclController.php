<?php
namespace Acl\Controller;

use Zend\View\Model\ViewModel;
use User\Service\Service as UserService;

class AclController extends AbstractBaseController
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
                ->getInstance('Acl\Model\Base');
        }

        return $this->model;
    }

    /**
     * Get resources
     */
    public function ajaxGetResourcesAction()
    {
        $view = new ViewModel(array(
            'resources' => $this->getModel()->
                getAllowedAclResources($this->getSlug(), UserService::getCurrentUserIdentity()->user_id)
        ));

        return $view;
    }
}
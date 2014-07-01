<?php
namespace Application\Controller;

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
                ->getInstance('Application\Model\Acl');
        }

        return $this->model;
    }

    /**
     * Get resources
     */
    public function getResourcesAction()
    {
        $view = new ViewModel(array(
            'resources' => $this->getModel()->
                getAllowedAclResources($this->getSlug(), UserService::getCurrentUserIdentity()->user_id)
        ));

        $view->setTerminal(true);
        return $view;
    }
}

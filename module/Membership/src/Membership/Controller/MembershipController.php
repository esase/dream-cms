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
use User\Service\Service as UserService;
use Membership\Model\Base as BaseMembershipModel;

class MembershipController extends AbstractBaseController
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
                ->getInstance('Membership\Model\Membership');
        }

        return $this->model;
    }

    /**
     * Membership levels list 
     */
    public function listAction()
    {
        if ($this->isGuest() || UserService::isDefaultUser()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get list of active membership levels
        $paginator = $this->getModel()->getMembershipLevels($this->getPage(), 
                null, 'title', 'asc', array('active' => BaseMembershipModel::MEMBERSHIP_LEVEL_STATUS_ACTIVE));

        return new ViewModel(array(
            'paginator' => $paginator,
        ));
    }
}
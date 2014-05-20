<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace XmlRpc\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Zend\XmlRpc\Server as XmlRpcServer;
use Zend\XmlRpc\Server\Fault as XmlRpcServerFault;
use User\Service\Service as UserService;
use stdClass;
use User\Model\Base as UserModelBase;

class XmlRpcController extends AbstractBaseController
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
                ->getInstance('XmlRpc\Model\XmlRpc');
        }

        return $this->model;
    }

    /**
     * Index page
     */
    public function indexAction()
    {
        // get user info by api key
        if (null != ($apiKey = $this->getRequest()->getQuery()->apiKey)) {
            if (null != ($userInfo =
                    UserService::getUserInfo($apiKey, UserModelBase::USER_INFO_BY_API_KEY))) {

                // fill the user's info
                $userIdentity = new stdClass();

                foreach($userInfo as $fieldName => $value) {
                    $userIdentity->$fieldName = $value;
                }

                // init user identity
                UserService::setCurrentUserIdentity($userIdentity);
            }
        }

        XmlRpcServerFault::attachFaultException('XmlRpc\Exception\XmlRpcActionDenied');

        $server = new XmlRpcServer();

        // get xmlrpc classes
        if (null != ($classes = $this->getModel()->getClasses())) {
            $server->sendArgumentsToAllMethods(false);

            foreach ($classes as $class) {
                $server->setClass($class['path'], $class['namespace'],  $this->getServiceLocator());
            }
        }

        $server->handle();

        // disable layout and view script
        return $this->response;
    }
}
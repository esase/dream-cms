<?php
namespace XmlRpc\Controller;

use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use User\Model\UserBase as UserModelBase;
use Zend\XmlRpc\Server as XmlRpcServer;
use Zend\XmlRpc\Server\Fault as XmlRpcServerFault;

class XmlRpcController extends ApplicationAbstractBaseController
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
            if (null != ($userInfo = UserIdentityService::getUserInfo($apiKey,
                    UserModelBase::USER_INFO_BY_API_KEY))) {

                // fill the user's info
                $userIdentity = [];

                foreach($userInfo as $fieldName => $value) {
                    $userIdentity[$fieldName] = $value;
                }

                // init user identity
                UserIdentityService::setCurrentUserIdentity($userIdentity);
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
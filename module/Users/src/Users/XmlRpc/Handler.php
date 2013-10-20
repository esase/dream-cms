<?php

namespace Users\XmlRpc;

use Application\XmlRpc\AbstractHandler;
use Users\Service\Service as UsersService;
use XmlRpc\Exception\XmlRpcActionDenied;
use DateTimeZone;
use Users\Event\Event as UsersEvent;
use Application\Model\Acl as AclModel;

class Handler extends AbstractHandler
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Request is denied (wrong time zone)
     */
    const REQUEST_DENIED_WRONG_TIME_ZONE = 'Time zone is wrong';

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Users\Model\XmlRpc');
        }

        return $this->model;
    }

    /**
     * Set user time zone
     *
     * @param string $timeZone
     * @param string $requestSignature
     * @return array
     */
    public function setUserTimeZone($timeZone, $requestSignature)
    {
        // check request signature
        if (!$this->isRequestAuthorized(array($timeZone), $requestSignature)) {
            throw new XmlRpcActionDenied(self::REQUEST_UNAUTHORIZED);
        }

        // check received time zone
        if (!in_array($timeZone, DateTimeZone::listidentifiers())) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED_WRONG_TIME_ZONE);
        }

        // check user permission
        if (!UsersService::checkPermission('users xmlrpc set user time zone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // update user's time zone
        if (true == ($result = $this->getModel()->setUserTimeZone($this->
                userIdentity->user_id, $timeZone))) {

            // fire event
            UsersEvent::fireEvent(UsersEvent::USER_SET_TIMEZONE_XMLRPC, $this->userIdentity->user_id,
                    $this->userIdentity->user_id, 'User set time zone via XmlRpc', array($this->userIdentity->nick_name));

            return self::SUCCESSFULLY_RESPONSE;
        }

        return self::REQUEST_BROKEN;
    }

    /**
     * Get user info
     *
     * @param integer $userId
     * @return array
     */
    public function getUserInfo($userId)
    {
        // check user permissions
        if (!UsersService::checkPermission('users xmlrpc view user info')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        if (false !== ($userInfo = $this->getModel()->getUserInfoById($userId))) {
            // fire event
            $eventDesc = $this->userIdentity->user_id == AclModel::DEFAULT_GUEST_ID
                ? 'User get info (guest) via XmlRpc'
                : 'User get info via XmlRpc';

            $eventDescParams = $this->userIdentity->user_id == AclModel::DEFAULT_GUEST_ID
                ? array($userInfo->nick_name)
                : array($this->userIdentity->nick_name, $userInfo->nick_name);

            UsersEvent::fireEvent(UsersEvent::USER_GET_INFO_XMLRPC,
                    $userInfo->user_id, $this->userIdentity->user_id, $eventDesc, $eventDescParams);

            return $userInfo;
        }

        return array();
    }
}
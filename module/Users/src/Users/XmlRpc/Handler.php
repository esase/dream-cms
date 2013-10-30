<?php

namespace Users\XmlRpc;

use Application\XmlRpc\AbstractHandler;
use Users\Service\Service as UsersService;
use XmlRpc\Exception\XmlRpcActionDenied;
use DateTimeZone;
use Users\Event\Event as UsersEvent;

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
        if (!UsersService::checkPermission('xmlrpc_set_user_timezone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // update user's time zone
        if (true == ($result = $this->getModel()->
                setUserTimeZone($this->userIdentity->user_id, $timeZone))) {

            // fire event
            UsersEvent::fireEvent(UsersEvent::USER_SET_TIMEZONE_XMLRPC, $this->userIdentity->user_id,
                    $this->userIdentity->user_id, 'Event - Set user\'s timezone via XmlRpc message', array($this->userIdentity->nick_name));

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
        if (!UsersService::checkPermission('xmlrpc_view_user_info')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // get user info
        if (false !== ($userInfo = $this->getModel()->getUserInfoById($userId))) {
            // fire event
            $eventDesc = UsersService::isGuest()
                ? 'Event - User get info (guest) via XmlRpc'
                : 'Event - User get info (user) via XmlRpc';

            $eventDescParams = UsersService::isGuest()
                ? array($userInfo->nick_name)
                : array($this->userIdentity->nick_name, $userInfo->nick_name);

            UsersEvent::fireEvent(UsersEvent::USER_GET_INFO_XMLRPC,
                    $userInfo->user_id, $this->userIdentity->user_id, $eventDesc, $eventDescParams);

            return $userInfo;
        }

        return array();
    }
}
<?php

namespace User\XmlRpc;

use Application\XmlRpc\AbstractHandler;
use User\Service\Service as UserService;
use XmlRpc\Exception\XmlRpcActionDenied;
use DateTimeZone;
use User\Event\Event as UserEvent;

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
                ->getInstance('User\Model\XmlRpc');
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

        // check an user's permission
        if (!UserService::checkPermission('xmlrpc_set_user_timezone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // update the user's time zone
        if (true == ($result = $this->getModel()->
                setUserTimeZone($this->userIdentity->user_id, $timeZone))) {

            // fire event
            UserEvent::fireEvent(UserEvent::SET_TIMEZONE_XMLRPC, $this->userIdentity->user_id,
                    $this->userIdentity->user_id, 'Event - Timezone set by user via XmlRpc', array($this->userIdentity->nick_name));

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
        if (!UserService::checkPermission('xmlrpc_view_user_info')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // get user info
        if (false !== ($userInfo = $this->getModel()->getUserInfo($userId))) {
            // fire event
            $eventDesc = UserService::isGuest()
                ? 'Event - User\'s info was obtained by guest via XmlRpc'
                : 'Event - User\'s info was obtained by user via XmlRpc';

            $eventDescParams = UserService::isGuest()
                ? array($userInfo->nick_name)
                : array($this->userIdentity->nick_name, $userInfo->nick_name);

            UserEvent::fireEvent(UserEvent::GET_INFO_XMLRPC,
                    $userInfo->user_id, $this->userIdentity->user_id, $eventDesc, $eventDescParams);

            return $userInfo;
        }

        return array();
    }
}
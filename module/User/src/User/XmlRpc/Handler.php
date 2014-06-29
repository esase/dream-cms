<?php
namespace User\XmlRpc;

use XmlRpc\Handler\AbstractHandler;
use XmlRpc\Exception\XmlRpcActionDenied;

use User\Service\Service as UserService;
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
    const REQUEST_DENIED_WRONG_TIME_ZONE = 'Time zone is wrong or not registered here';

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

        // check an user's permission
        if (!UserService::checkPermission('xmlrpc_set_user_timezone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // check received time zone
        if (false === ($timeZoneId = array_search($timeZone, 
                UserService::getTimeZones()))) {

            throw new XmlRpcActionDenied(self::REQUEST_DENIED_WRONG_TIME_ZONE);
        }

        // update the user's time zone
        if (true == ($result = $this->getModel()->
                setUserTimeZone($this->userIdentity->user_id, $timeZoneId))) {

            // fire set user's time zone via XmlRpc event
            UserEvent::fireSetTimezoneViaXmlRpcEvent($this->
                    userIdentity->user_id, $this->userIdentity->nick_name);

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
            $viewerNickName = !UserService::isGuest()
                ? $this->userIdentity->nick_name
                : '';

            // fire the get user info via XmlRpc event
            UserEvent::fireGetUserInfoViaXmlRpcEvent($userInfo->user_id, 
                    $userInfo->nick_name, $this->userIdentity->user_id, $viewerNickName);

            return $userInfo;
        }

        return array();
    }
}
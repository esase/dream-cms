<?php
namespace User\XmlRpc;

use Acl\Service\Acl as AclService;
use Application\Service\ApplicationTimeZone as TimeZoneService;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;
use XmlRpc\Handler\XmlRpcAbstractHandler;
use XmlRpc\Exception\XmlRpcActionDenied;

class UserHandler extends XmlRpcAbstractHandler
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
                ->getInstance('User\Model\UserXmlRpc');
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
        if (!$this->isRequestAuthorized([$timeZone], $requestSignature)) {
            throw new XmlRpcActionDenied(self::REQUEST_UNAUTHORIZED);
        }

        // check an user's permission
        if (!AclService::checkPermission('xmlrpc_set_user_timezone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // check received time zone
        if (false === ($timeZoneId =
                array_search($timeZone,TimeZoneService::getTimeZones()))) {

            throw new XmlRpcActionDenied(self::REQUEST_DENIED_WRONG_TIME_ZONE);
        }

        // update the user's time zone
        if (true == ($result = $this->getModel()->setUserTimeZone($this->
                userIdentity['user_id'], $this->userIdentity['nick_name'], $timeZoneId))) {

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
        if (!AclService::checkPermission('xmlrpc_view_user_info')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        $viewerNickName = !UserIdentityService::isGuest()
            ? $this->userIdentity['nick_name']
            : null;

        // get user info
        if (false !== ($userInfo = $this->getModel()->getXmlRpcUserInfo($userId, $this->
                userIdentity['user_id'], $viewerNickName))) {

            return $userInfo;
        }

        return [];
    }
}
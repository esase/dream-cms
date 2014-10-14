<?php
namespace XmlRpc\Handler;

use User\Service\UserIdentity as UserIdentityService;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class XmlRpcAbstractHandler
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * User identity
     * @var array
     */
    protected $userIdentity;

    /**
     * Successfully response flag
     */
    const SUCCESSFULLY_RESPONSE = 'ok';

    /**
     * Request is denied
     */
    const REQUEST_DENIED = 'Action is denied';

    /**
     * Request is unauthorized
     */
    const REQUEST_UNAUTHORIZED = 'Request is unauthorized';

    /**
     * Request is broken
     */
    const REQUEST_BROKEN = 'Error occured';

    /**
     * Class constructor
     *
     * @param object $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->userIdentity = UserIdentityService::getCurrentUserIdentity();
    }

    /**
     * Check request authorization
     *
     * @param array $args
     * @param string $requestSignature
     * @return boolean
     */
    protected function isRequestAuthorized(array $args,  $requestSignature)
    {
        // check user api secret
        if (empty($this->userIdentity['api_secret'])) {
            return false;
        }

        asort($args);

        return $requestSignature ==
                md5(implode(':', array_merge($args, [$this->userIdentity['api_secret']])));
    }
}
<?php

namespace Application\XmlRpc;

use Zend\ServiceManager\ServiceManager;
use Users\Service\Service as UsersService;

abstract class AbstractHandler
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * User identity
     * @var object
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
     * @param object $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->userIdentity = UsersService::getCurrentUserIdentity();
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
        if (empty($this->userIdentity->api_secret)) {
            return false;
        }

        asort($args);

        return $requestSignature ==
                md5(implode(':', array_merge($args, array($this->userIdentity->api_secret))));
    }
}
<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace XmlRpc\Handler;

use User\Service\UserIdentity as UserIdentityService;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class XmlRpcAbstractHandler
{
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
    const REQUEST_BROKEN = 'Error occurred';

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * User identity
     *
     * @var array
     */
    protected $userIdentity;

    /**
     * Class constructor
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
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

        return $requestSignature == md5(implode(':', array_merge($args, [$this->userIdentity['api_secret']])));
    }
}
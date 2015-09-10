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
     *
     * @var \XmlRpc\Model\XmlRpc
     */
    protected $model;

    /**
     * Get model
     *
     * @return \XmlRpc\Model\XmlRpc
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
        // get user info by the api key
        if (null != ($apiKey = $this->getRequest()->getQuery()->api_key)) {
            if (null != ($userInfo =
                    UserIdentityService::getUserInfo($apiKey, UserModelBase::USER_INFO_BY_API_KEY))) {

                // fill the user's info
                if ($userInfo['status'] == UserModelBase::STATUS_APPROVED) {
                    $userIdentity = [];
    
                    foreach($userInfo as $fieldName => $value) {
                        $userIdentity[$fieldName] = $value;
                    }
    
                    // init user identity
                    UserIdentityService::setCurrentUserIdentity($userIdentity);
                }
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
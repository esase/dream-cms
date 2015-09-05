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
return [
    'Acl\Form\AclResourceFilter'                 => __DIR__ . '/src/Acl/Form/AclResourceFilter.php',
    'Acl\Form\AclResourceSetting'                => __DIR__ . '/src/Acl/Form/AclResourceSetting.php',
    'Acl\Form\AclRole'                           => __DIR__ . '/src/Acl/Form/AclRole.php',
    'Acl\Form\AclRoleFilter'                     => __DIR__ . '/src/Acl/Form/AclRoleFilter.php',
    'Acl\Event\AclEvent'                         => __DIR__ . '/src/Acl/Event/AclEvent.php',
    'Acl\Service\Acl'                            => __DIR__ . '/src/Acl/Service/Acl.php',
    'Acl\Model\AclBase'                          => __DIR__ . '/src/Acl/Model/AclBase.php',
    'Acl\Model\AclAdministration'                => __DIR__ . '/src/Acl/Model/AclAdministration.php',
    'Acl\Controller\Plugin\AclCheckPermission'   => __DIR__ . '/src/Acl/Controller/Plugin/AclCheckPermission.php',
    'Acl\Controller\AclAdministrationController' => __DIR__ . '/src/Acl/Controller/AclAdministrationController.php',
    'Acl\Exception\AclException'                 => __DIR__ . '/src/Acl/Exception/AclException.php',
    'Acl\View\Helper\AclCheckPermission'         => __DIR__ . '/src/Acl/View/Helper/AclCheckPermission.php',
    'Acl\View\Helper\AclRoutePermission'         => __DIR__ . '/src/Acl/View/Helper/AclRoutePermission.php',
    'Acl\Module'                                 => __DIR__ . '/Module.php',
    'Acl\Test\AclBootstrap'                      => __DIR__ . '/test/Bootstrap.php',
    'Acl\Test\Service\ServiceTest'               => __DIR__ . '/test/Acl/src/Acl/Service/AclTest.php',
];

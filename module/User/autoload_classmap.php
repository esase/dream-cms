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
    'User\PagePrivacy\UserEditPrivacy'                   => __DIR__ . '/src/User/PagePrivacy/UserEditPrivacy.php',
    'User\PagePrivacy\UserViewPrivacy'                   => __DIR__ . '/src/User/PagePrivacy/UserViewPrivacy.php',
    'User\PagePrivacy\UserActivatePrivacy'               => __DIR__ . '/src/User/PagePrivacy/UserActivatePrivacy.php',
    'User\PagePrivacy\UserPasswordResetPrivacy'          => __DIR__ . '/src/User/PagePrivacy/UserPasswordResetPrivacy.php',
    'User\PagePrivacy\UserLoginPrivacy'                  => __DIR__ . '/src/User/PagePrivacy/UserLoginPrivacy.php',
    'User\PagePrivacy\UserRegisterPrivacy'               => __DIR__ . '/src/User/PagePrivacy/UserRegisterPrivacy.php',
    'User\PagePrivacy\UserDeletePrivacy'                 => __DIR__ . '/src/User/PagePrivacy/UserDeletePrivacy.php',
    'User\PagePrivacy\UserForgotPrivacy'                 => __DIR__ . '/src/User/PagePrivacy/UserForgotPrivacy.php',
    'User\PagePrivacy\UserDashboardPrivacy'              => __DIR__ . '/src/User/PagePrivacy/UserDashboardPrivacy.php',
    'User\Form\UserDelete'                               => __DIR__ . '/src/User/Form/UserDelete.php',
    'User\Form\UserForgot'                               => __DIR__ . '/src/User/Form/UserForgot.php',
    'User\Form\UserActivationCode'                       => __DIR__ . '/src/User/Form/UserActivationCode.php',
    'User\Form\UserFilter'                               => __DIR__ . '/src/User/Form/UserFilter.php',
    'User\Form\User'                                     => __DIR__ . '/src/User/Form/User.php',
    'User\Form\UserLogin'                                => __DIR__ . '/src/User/Form/UserLogin.php',
    'User\Form\UserRole'                                 => __DIR__ . '/src/User/Form/UserRole.php',
    'User\Event\UserEvent'                               => __DIR__ . '/src/User/Event/UserEvent.php',
    'User\Service\UserIdentity'                          => __DIR__ . '/src/User/Service/UserIdentity.php',
    'User\XmlRpc\UserHandler'                            => __DIR__ . '/src/User/XmlRpc/UserHandler.php',
    'User\PageProvider\UserPageProvider'                 => __DIR__ . '/src/User/PageProvider/UserPageProvider.php',
    'User\Utility\UserAuthenticate'                      => __DIR__ . '/src/User/Utility/UserAuthenticate.php',
    'User\Utility\UserCache'                             => __DIR__ . '/src/User/Utility/UserCache.php',
    'User\Model\UserWidget'                              => __DIR__ . '/src/User/Model/UserWidget.php',
    'User\Model\UserXmlRpc'                              => __DIR__ . '/src/User/Model/UserXmlRpc.php',
    'User\Model\UserAdministration'                      => __DIR__ . '/src/User/Model/UserAdministration.php',
    'User\Model\UserBase'                                => __DIR__ . '/src/User/Model/UserBase.php',
    'User\Model\UserAjax'                                => __DIR__ . '/src/User/Model/UserAjax.php',
    'User\Controller\UserAdministrationController'       => __DIR__ . '/src/User/Controller/UserAdministrationController.php',
    'User\Controller\Plugin\UserIdentity'                => __DIR__ . '/src/User/Controller/Plugin/UserIdentity.php',
    'User\Controller\UserAjaxController'                 => __DIR__ . '/src/User/Controller/UserAjaxController.php',
    'User\Exception\UserException'                       => __DIR__ . '/src/User/Exception/UserException.php',
    'User\View\Widget\UserDashboardWidget'               => __DIR__ . '/src/User/View/Widget/UserDashboardWidget.php',
    'User\View\Widget\UserEditWidget'                    => __DIR__ . '/src/User/View/Widget/UserEditWidget.php',
    'User\View\Widget\UserInfoWidget'                    => __DIR__ . '/src/User/View/Widget/UserInfoWidget.php',
    'User\View\Widget\UserDashboardAdministrationWidget' => __DIR__ . '/src/User/View/Widget/UserDashboardAdministrationWidget.php',
    'User\View\Widget\UserActivateWidget'                => __DIR__ . '/src/User/View/Widget/UserActivateWidget.php',
    'User\View\Widget\UserDeleteWidget'                  => __DIR__ . '/src/User/View/Widget/UserDeleteWidget.php',
    'User\View\Widget\UserForgotWidget'                  => __DIR__ . '/src/User/View/Widget/UserForgotWidget.php',
    'User\View\Widget\UserLoginWidget'                   => __DIR__ . '/src/User/View/Widget/UserLoginWidget.php',
    'User\View\Widget\UserDashboardUserInfoWidget'       => __DIR__ . '/src/User/View/Widget/UserDashboardUserInfoWidget.php',
    'User\View\Widget\UserPasswordResetWidget'           => __DIR__ . '/src/User/View/Widget/UserPasswordResetWidget.php',
    'User\View\Widget\UserAvatarWidget'                  => __DIR__ . '/src/User/View/Widget/UserAvatarWidget.php',
    'User\View\Widget\UserRegisterWidget'                => __DIR__ . '/src/User/View/Widget/UserRegisterWidget.php',
    'User\View\Widget\UserAbstractWidget'                => __DIR__ . '/src/User/View/Widget/UserAbstractWidget.php',
    'User\View\Helper\UserIdentity'                      => __DIR__ . '/src/User/View/Helper/UserIdentity.php',
    'User\View\Helper\UserMenu'                          => __DIR__ . '/src/User/View/Helper/UserMenu.php',
    'User\View\Helper\UserIsGuest'                       => __DIR__ . '/src/User/View/Helper/UserIsGuest.php',
    'User\View\Helper\UserAvatarUrl'                     => __DIR__ . '/src/User/View/Helper/UserAvatarUrl.php',
    'User\Module'                                        => __DIR__ . '/Module.php',
    'User\Test\UserBootstrap'                            => __DIR__ . '/test/Bootstrap.php',
    'User\Test\Event\DeleteRoleTest'                     => __DIR__ . '/test/User/src/User/Event/DeleteRoleTest.php',
];

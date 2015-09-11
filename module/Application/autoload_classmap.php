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
    'Application\Form\ApplicationClearCache'                             => __DIR__ . '/src/Application/Form/ApplicationClearCache.php',
    'Application\Form\ApplicationFormManager'                            => __DIR__ . '/src/Application/Form/ApplicationFormManager.php',
    'Application\Form\ApplicationSetting'                                => __DIR__ . '/src/Application/Form/ApplicationSetting.php',
    'Application\Form\ApplicationCustomFormInterface'                    => __DIR__ . '/src/Application/Form/ApplicationCustomFormInterface.php',
    'Application\Form\ApplicationAbstractCustomForm'                     => __DIR__ . '/src/Application/Form/ApplicationAbstractCustomForm.php',
    'Application\Form\ApplicationCustomFormBuilder'                      => __DIR__ . '/src/Application/Form/ApplicationCustomFormBuilder.php',
    'Application\Form\ApplicationModuleFilter'                           => __DIR__ . '/src/Application/Form/ApplicationModuleFilter.php',
    'Application\Form\ApplicationModule'                                 => __DIR__ . '/src/Application/Form/ApplicationModule.php',
    'Application\Event\ApplicationEvent'                                 => __DIR__ . '/src/Application/Event/ApplicationEvent.php',
    'Application\Event\ApplicationAbstractEvent'                         => __DIR__ . '/src/Application/Event/ApplicationAbstractEvent.php',
    'Application\Service\Application'                                    => __DIR__ . '/src/Application/Service/Application.php',
    'Application\Service\ApplicationSetting'                             => __DIR__ . '/src/Application/Service/ApplicationSetting.php',
    'Application\Service\ApplicationCaptcha'                             => __DIR__ . '/src/Application/Service/ApplicationCaptcha.php',
    'Application\Service\ApplicationServiceLocator'                      => __DIR__ . '/src/Application/Service/ApplicationServiceLocator.php',
    'Application\Service\ApplicationTimeZone'                            => __DIR__ . '/src/Application/Service/ApplicationTimeZone.php',
    'Application\Utility\ApplicationImage'                               => __DIR__ . '/src/Application/Utility/ApplicationImage.php',
    'Application\Utility\ApplicationFtp'                                 => __DIR__ . '/src/Application/Utility/ApplicationFtp.php',
    'Application\Utility\ApplicationPagination'                          => __DIR__ . '/src/Application/Utility/ApplicationPagination.php',
    'Application\Utility\ApplicationErrorLogger'                         => __DIR__ . '/src/Application/Utility/ApplicationErrorLogger.php',
    'Application\Utility\ApplicationSlug'                                => __DIR__ . '/src/Application/Utility/ApplicationSlug.php',
    'Application\Utility\ApplicationFileSystem'                          => __DIR__ . '/src/Application/Utility/ApplicationFileSystem.php',
    'Application\Utility\ApplicationEmailNotification'                   => __DIR__ . '/src/Application/Utility/ApplicationEmailNotification.php',
    'Application\Utility\ApplicationDisableSite'                         => __DIR__ . '/src/Application/Utility/ApplicationDisableSite.php',
    'Application\Utility\ApplicationCache'                               => __DIR__ . '/src/Application/Utility/ApplicationCache.php',
    'Application\Utility\ApplicationRouteParam'                          => __DIR__ . '/src/Application/Utility/ApplicationRouteParam.php',
    'Application\Model\ApplicationSettingAdministration'                 => __DIR__ . '/src/Application/Model/ApplicationSettingAdministration.php',
    'Application\Model\ApplicationInit'                                  => __DIR__ . '/src/Application/Model/ApplicationInit.php',
    'Application\Model\ApplicationAdminMenu'                             => __DIR__ . '/src/Application/Model/ApplicationAdminMenu.php',
    'Application\Model\ApplicationAbstractNestedSet'                     => __DIR__ . '/src/Application/Model/ApplicationAbstractNestedSet.php',
    'Application\Model\ApplicationSetting'                               => __DIR__ . '/src/Application/Model/ApplicationSetting.php',
    'Application\Model\ApplicationModelManager'                          => __DIR__ . '/src/Application/Model/ApplicationModelManager.php',
    'Application\Model\ApplicationAbstractSetting'                       => __DIR__ . '/src/Application/Model/ApplicationAbstractSetting.php',
    'Application\Model\ApplicationEmailQueue'                            => __DIR__ . '/src/Application/Model/ApplicationEmailQueue.php',
    'Application\Model\ApplicationDeleteContent'                         => __DIR__ . '/src/Application/Model/ApplicationDeleteContent.php',
    'Application\Model\ApplicationTimeZone'                              => __DIR__ . '/src/Application/Model/ApplicationTimeZone.php',
    'Application\Model\ApplicationAbstractBase'                          => __DIR__ . '/src/Application/Model/ApplicationAbstractBase.php',
    'Application\Model\ApplicationModuleAdministration'                  => __DIR__ . '/src/Application/Model/ApplicationModuleAdministration.php',
    'Application\Model\ApplicationBase'                                  => __DIR__ . '/src/Application/Model/ApplicationBase.php',
    'Application\Controller\ApplicationSettingAdministrationController'  => __DIR__ . '/src/Application/Controller/ApplicationSettingAdministrationController.php',
    'Application\Controller\ApplicationModuleAdministrationController'   => __DIR__ . '/src/Application/Controller/ApplicationModuleAdministrationController.php',
    'Application\Controller\Plugin\ApplicationSetting'                   => __DIR__ . '/src/Application/Controller/Plugin/ApplicationSetting.php',
    'Application\Controller\ApplicationAbstractAdministrationController' => __DIR__ . '/src/Application/Controller/ApplicationAbstractAdministrationController.php',
    'Application\Controller\ApplicationAbstractBaseController'           => __DIR__ . '/src/Application/Controller/ApplicationAbstractBaseController.php',
    'Application\Controller\ApplicationLoginAdministrationController'    => __DIR__ . '/src/Application/Controller/ApplicationLoginAdministrationController.php',
    'Application\Controller\ApplicationErrorController'                  => __DIR__ . '/src/Application/Controller/ApplicationErrorController.php',
    'Application\Controller\ApplicationAbstractBaseConsoleController'    => __DIR__ . '/src/Application/Controller/ApplicationAbstractBaseConsoleController.php',
    'Application\Controller\ApplicationEmailQueueConsoleController'      => __DIR__ . '/src/Application/Controller/ApplicationEmailQueueConsoleController.php',
    'Application\Controller\ApplicationDeleteContentConsoleController'   => __DIR__ . '/src/Application/Controller/ApplicationDeleteContentConsoleController.php',
    'Application\Exception\ApplicationException'                         => __DIR__ . '/src/Application/Exception/ApplicationException.php',
    'Application\View\Helper\ApplicationHumanDate'                       => __DIR__ . '/src/Application/View/Helper/ApplicationHumanDate.php',
    'Application\View\Helper\ApplicationConfig'                          => __DIR__ . '/src/Application/View/Helper/ApplicationConfig.php',
    'Application\View\Helper\ApplicationCalendar'                        => __DIR__ . '/src/Application/View/Helper/ApplicationCalendar.php',
    'Application\View\Helper\ApplicationAdminMenu'                       => __DIR__ . '/src/Application/View/Helper/ApplicationAdminMenu.php',
    'Application\View\Helper\ApplicationRoute'                           => __DIR__ . '/src/Application/View/Helper/ApplicationRoute.php',
    'Application\View\Helper\ApplicationBooleanValue'                    => __DIR__ . '/src/Application/View/Helper/ApplicationBooleanValue.php',
    'Application\View\Helper\ApplicationSetting'                         => __DIR__ . '/src/Application/View/Helper/ApplicationSetting.php',
    'Application\View\Helper\ApplicationFlashMessage'                    => __DIR__ . '/src/Application/View/Helper/ApplicationFlashMessage.php',
    'Application\View\Helper\ApplicationRandId'                          => __DIR__ . '/src/Application/View/Helper/ApplicationRandId.php',
    'Application\View\Helper\ApplicationIp'                              => __DIR__ . '/src/Application/View/Helper/ApplicationIp.php',
    'Application\View\Helper\ApplicationDate'                            => __DIR__ . '/src/Application/View/Helper/ApplicationDate.php',
    'Application\View\Helper\ApplicationFileSize'                        => __DIR__ . '/src/Application/View/Helper/ApplicationFileSize.php',
    'Application\Module'                                                 => __DIR__ . '/Module.php',
    'Application\Test\ApplicationBootstrap'                              => __DIR__ . '/test/Bootstrap.php',
    'Application\Test\Form\ApplicationCustomFormBuilderTest'             => __DIR__ . '/test/Application/src/Application/Form/ApplicationCustomFormBuilderTest.php',
    'Application\Test\Service\ServiceTest'                               => __DIR__ . '/test/Application/src/Application/Service/ApplicationServiceTest.php',
    'Application\Test\Utility\ApplicationSlugUtilityTest'                => __DIR__ . '/test/Application/src/Application/Utility/ApplicationSlugTest.php',
    'Application\Test\Utility\ApplicationCacheTest'                      => __DIR__ . '/test/Application/src/Application/Utility/ApplicationCacheTest.php',
    'Application\Test\Model\ApplicationSlugTest'                         => __DIR__ . '/test/Application/src/Application/Model/ApplicationSlugTest.php',
    'Application\DeleteContent\ApplicationAbstractDeleteContent'         => __DIR__ . '/src/Application/DeleteContent/ApplicationAbstractDeleteContent.php'
];

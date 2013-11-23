<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Home',
                        'action' => 'index'
                    ),
                ),
            ),
            'application' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/[:languge[/:controller[/:action[/:id][/page/:page][/order_by/:order_by][/:order_type]]]]',
                    'constraints' => array(
                        'languge' => '[a-z]{2}',
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                        'page' => '[0-9]+',
                        'order_by' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'order_type' => 'asc|desc',
                    ),
                    'defaults' => array(
                        'controller' => 'Home',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Wildcard',
                        'options' => array(
                        )
                    )
                )
            )
        )
    ),
    'service_manager' => array(
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'translation_file_patterns' => array(
            array(
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'home' => 'Application\Controller\IndexController',
            'error' => 'Application\Controller\ErrorController',
            'modules_administration' => 'Application\Controller\ModuleAdministrationController',
            'settings_administration' => 'Application\Controller\SettingAdministrationController'
        )
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'checkPermission' => 'Application\Controller\Plugin\CheckPermission',
            'isGuest' => 'Application\Controller\Plugin\IsGuest',
        )
    ),
    'view_manager' => array(
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
        )
    )
);

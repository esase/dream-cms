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
                        'controller' => 'Page',
                        'action' => 'index'
                    ),
                ),
            ),
            'administration' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/administration'
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'base' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'priority' => 1,
                            'route'    => '/[:languge[/:controller[/:action[/page/:page][/per-page/:per_page][/order-by/:order_by][/order-type/:order_type][/:slug][/:extra]]]][/]',
                            'constraints' => array(
                                'languge' => '[a-z]{2}',
                                'controller' => '[a-z][a-z0-9-]*',
                                'action' => '[a-z][a-z0-9-]*',
                                'page' => '[0-9]+',
                                'per_page' => '[0-9]+',
                                'order_by' => '[a-z][a-z0-9-]*',
                                'order_type' => 'asc|desc',
                                'slug'     => '[0-9a-zA-Z-_]+',
                                'extra'    => '[0-9a-zA-Z-_]+'
                            ),
                            'defaults' => array(
                                'controller' => 'Page',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true
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
            'error' => 'Application\Controller\ErrorController',
            'modules-administration' => 'Application\Controller\ModuleAdministrationController',
            'settings-administration' => 'Application\Controller\SettingAdministrationController',
            'acl-administration' => 'Application\Controller\AclAdministrationController',
            'acl' => 'Application\Controller\AclController'
        )
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'getSetting' => 'Application\Controller\Plugin\Setting'
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

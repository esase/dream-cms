<?php
namespace User;

use User\Event\Event as UserEvent;
use User\Service\UserIdentity as UserIdentityService;
use User\Model\Base as UserBaseModel;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Console\Request as ConsoleRequest;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Localization\Module as LocalizationModule;
use Acl\Event\Event as AclEvent;
use Acl\Model\Base as AclModelBase;
use Application\Model\Acl as AclModel;
use Application\Utility\ErrorLogger;
use Application\Service\Setting as SettingService;
use Application\Service\Application as ApplicationService;
use Exception;

class Module
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * User identity
     * @var array
     */
    protected $userIdentity;

    /**
     * Init
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);

        $eventManager = AclEvent::getEventManager();
        $eventManager->attach(AclEvent::DELETE_ACL_ROLE, function ($e) use ($moduleManager) {
            $users = $moduleManager->getEvent()->getParam('ServiceManager')
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\Base');

            // change the empty role with the default role
            if (null != ($usersList = $users->getUsersWithEmptyRole())) {
                // process users list
                foreach ($usersList as $userInfo) {
                    $users->editUserRole($userInfo['user_id'], 
                            AclModel::DEFAULT_ROLE_MEMBER, AclModel::DEFAULT_ROLE_MEMBER_NAME, $userInfo, true);
                }
            }
        }, -100);
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(ModuleEvent $e)
    {
        // init user identity
        $this->initUserIdentity();

        // init time zone
        $this->initTimeZone();
    }

    /**
     * Init time zone
     */
    protected function initTimeZone()
    {
        try {
            // get list of all registered time zones
            $timeZone  = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\TimeZone');

            $registeredTimeZones = $timeZone->getTimeZones();

            // what should we use here, user's or default time zone
            $defaultTimeZone = !empty($this->userIdentity['time_zone_name'])
                ? $this->userIdentity['time_zone_name']
                : SettingService::getSetting('application_default_time_zone');

            // check default time zone existing
            if (!in_array($defaultTimeZone, $registeredTimeZones)) {
                $defaultTimeZone = current($registeredTimeZones);
            }

            // change time zone settings
            if ($defaultTimeZone != date_default_timezone_get()) {
                date_default_timezone_set($defaultTimeZone);
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init identity
     */
    protected function initUserIdentity()
    {
        try {
            $authService = $this->serviceManager->get('User\AuthService');
    
            // set identity as a site guest
            if (!$authService->hasIdentity()) {
                $this->initGuestIdentity($authService);
            }
            else {
                $this->userIdentity = $authService->getIdentity();

                // get extended user info
                if ($authService->getIdentity()['user_id'] != UserBaseModel::DEFAULT_GUEST_ID) {
                    $user = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('User\Model\Base');
    
                    if (null != ($userInfo = $user->getUserInfo($authService->getIdentity()['user_id']))) {
                        // fill the user identity with data
                        foreach($userInfo as $fieldName => $value) {
                            $this->userIdentity[$fieldName] = $value;
                        }
                    }
                    else {
                        // user not found, set the current user as a site guest
                        $this->initGuestIdentity($authService);
                    }
                }
            }

            // set the user identity
            UserIdentityService::setCurrentUserIdentity($this->userIdentity);
            
            
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init guest identity
     *
     * @param object $authService
     * @return void
     */
    protected function initGuestIdentity($authService)
    {
        try {
            $this->userIdentity = [];
            $this->userIdentity['role'] = AclModelBase::DEFAULT_ROLE_GUEST;
            $this->userIdentity['user_id'] = UserBaseModel::DEFAULT_GUEST_ID;

            $request = $this->serviceManager->get('Request');

            // get language from cookie
            if (!$request instanceof ConsoleRequest) {
                $this->userIdentity['language'] = isset($request->getCookie()->{LocalizationModule::LOCALIZATION_COOKIE})
                    ? $request->getCookie()->{LocalizationModule::LOCALIZATION_COOKIE}
                    : null;
            }

            $authService->getStorage()->write($this->userIdentity);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'User\AuthService' => function($serviceManager) {
                    $authAdapter = new DbTableAuthAdapter($serviceManager->get('Zend\Db\Adapter\Adapter'), 'user_list', 
                            'nick_name', 'password', 'SHA1(CONCAT(MD5(?), salt)) AND status = "' . UserBaseModel::STATUS_APPROVED . '"');

                    $authService = new AuthenticationService();
                    $authService->setAdapter($authAdapter);

                    return $authService;
                }
            ]
        ];
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return [
            'invokables' => [
                'userLoginWidget' => 'User\View\Widget\UserLoginWidget',
                'userRegisterWidget' => 'User\View\Widget\UserRegisterWidget',
                'userActivateWidget' => 'User\View\Widget\UserActivateWidget',
                'userForgotWidget' => 'User\View\Widget\UserForgotWidget',
                'userPasswordResetWidget' => 'User\View\Widget\UserPasswordResetWidget',
                'userDeleteWidget' => 'User\View\Widget\UserDeleteWidget'
            ],
            'factories' => [
                'userAvatarUrl' => function(){
                    $thumbDir  = ApplicationService::getResourcesUrl() . UserModelBase::getThumbnailsDir();
                    $avatarDir = ApplicationService::getResourcesUrl() . UserModelBase::getAvatarsDir();

                    return new \User\View\Helper\UserAvatarUrl($thumbDir, $avatarDir);
                },
                'userMenu' => function() {
                    $userMenu = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('User\Model\UserMenu');

                    return new \User\View\Helper\UserMenu($userMenu->getMenu());
                },
                'userIdentity' => function() {
                    return new \User\View\Helper\UserIdentity(UserIdentityService::getCurrentUserIdentity());
                },
                'userIsGuest' => function() {
                    return new \User\View\Helper\UserIsGuest(UserIdentityService::isGuest());
                }
            ]
        ];
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
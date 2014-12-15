<?php
namespace Application\Model;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use DirectoryIterator;
use CallbackFilterIterator;

class ApplicationModuleAdministration extends ApplicationBase
{
    /**
     * Module config
     * @var string
     */
    protected $moduleConfig = '/config/module.config.php';

    /**
     * Module install config
     * @var string
     */
    protected $moduleInstallConfig = '/config/module.config.install.php';

    /**
     * Get modules dir
     *
     * @return string
     */
    public function getModulesDir()
    {
        return APPLICATION_ROOT . '/module/';
    }

    /**
     * Is custom module
     *
     * @param string $module
     * @return boolean
     */
    public function isCustomModule($module)
    {
        $moduleDirectory = $this->getModulesDir() . basename($module);
        return file_exists($moduleDirectory . $this->
                moduleInstallConfig) && file_exists($moduleDirectory . $this->moduleConfig);
    }

    /**
     * Get custom module config
     *
     * @param string $module
     * @return boolean|array
     */
    public function getCustomModuleConfig($module, $type = 'install', $checkExisting = true)
    {
        // is custom module
        if ($checkExisting && false === ($result = $this->isCustomModule($module))) {
            return $result;
        }

        switch ($type) {
            case 'install' :
                return include $this->getModulesDir() . basename($module) . $this->moduleInstallConfig;

            case 'system' :
            default :
                return include $this->getModulesDir() . basename($module) . $this->moduleConfig;
        }
    }

    /**
     * Get not installed modules
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return array
     */
    public function getNotInstalledModules($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
            'vendor',
            'vendor_email',
            'version',
            'date'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? SORT_DESC
            : SORT_ASC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'date';

        // get installed active modules
        $installedModules = array_map('strtolower', $this->getActiveModulesList());

        // get all directories and files
        $directoryIterator = new DirectoryIterator($this->getModulesDir());
        $modules = new CallbackFilterIterator($directoryIterator, function($current, $key, $iterator) use ($installedModules) {
            // skip already installed modules
            if ($current->isDot() || !$current->isDir()
                    || in_array(strtolower($current->getFileName()), $installedModules)) {

                return false;
            }

            // check module
            return $this->isCustomModule($current->getFileName());
        });

        $processedModules = [];
        $orderValues    = [];

        // process not installed modules
        foreach($modules as $data) {
            $moduleInstallConfig = $this->getCustomModuleConfig($data->getFileName(), 'install', false);

            $moduleInfo = [
                'name' => $data->getFileName(),
                'vendor' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['vendor'] : null,
                'email' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['vendor_email'] : null,
                'version' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['version'] : null,
                'description' => !empty($moduleInstallConfig['description']) ? $moduleInstallConfig['description'] : null,
                'date' => $data->getMTime(),
                'module_depends' => $this->checkCustomModuleDepends($moduleInstallConfig),
                'system_requirements' => count($this->getNotValidatedCustomModuleSystemRequirements($moduleInstallConfig)) ? false : true
            ];

            $processedModules[] = $moduleInfo; 
            $orderValues[]    = $moduleInfo[$orderBy];

            // load the module's translations
            $this->addCustomModuleTranslations($this->getCustomModuleConfig($data->getFileName(), 'system', false));
        }

        array_multisort($orderValues, $orderType, $processedModules);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedModules));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get dependent modules
     *
     * @param string|integer $moduleName
     * @return array
     */
    public function getDependentModules($moduleName)
    {
        $modules = [];

        // try to get dependent modules from the installed module
        if (null != ($moduleInfo = $this->getModuleInfo($moduleName))) {
            // get all dependent modules
            $select = $this->select();
            $select->from(['a' => 'application_module_depend'])
                ->columns([])
                ->join(
                    ['b' => 'application_module'],
                    'a.depend_module_id = b.id',
                    [
                        'name',
                        'vendor'
                    ]
                )
                ->where(['module_id' => $moduleInfo['id']]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            
            foreach ($resultSet as $module) {
                $modules[] = [
                    'module' => $module['name'],
                    'vendor' => $module['vendor'] 
                ];
            }
        }
        else {
            // try to get dependent modules from the not installed module
            if (false !== ($moduleInstallConfig = $this->getCustomModuleConfig($moduleName, 'install'))) {
                if (!empty($moduleInstallConfig['module_depends'])) {
                    foreach ($moduleInstallConfig['module_depends'] as $module) {
                        $moduleInfo = $this->getModuleInfo($module['module']);

                        if (!$moduleInfo
                                || strcasecmp($moduleInfo['vendor'], $module['vendor']) != 0
                                || $moduleInfo['status'] != self::MODULE_STATUS_ACTIVE) {

                            $modules[] = [
                                'module' => $module['module'],
                                'vendor' => $module['vendor']
                            ];

                            // load the module's translations
                            if (false !== ($dependInstallConfig =
                                    $this->getCustomModuleConfig($module['module'], 'system'))) {

                                $this->addCustomModuleTranslations($dependInstallConfig);
                            }
                        }
                    }
                }
            }
        }

        return $modules;
    }

    /**
     * Get module description
     *
     * @param string|integer $module
     * @return string|boolean
     */
    public function getModuleDescription($module)
    {
        // try to get description from the installed module
        if (null != ($moduleInfo = $this->getModuleInfo($module))) {
            return !empty($moduleInfo['description']) ? $moduleInfo['description'] : false;
        }

        // try to get description from the not installed module
        if (false !== ($moduleInstallConfig = $this->getCustomModuleConfig($module, 'install'))) {
            // load the module's translations
            $this->addCustomModuleTranslations($this->getCustomModuleConfig($module, 'system', false));

            return !empty($moduleInstallConfig['description'])
                ? $moduleInstallConfig['description']
                : false;
        }

        return false;
    }

    /**
     * Check custom module depends
     *
     * @param array $installConfig
     * @return boolean
     */
    protected function checkCustomModuleDepends(array $installConfig)
    {
        if (!empty($installConfig['module_depends'])) {
            foreach ($installConfig['module_depends'] as $module) {
                $moduleInfo = $this->getModuleInfo($module['module']);

                if (!$moduleInfo
                        || strcasecmp($moduleInfo['vendor'], $module['vendor']) != 0
                        || $moduleInfo['status'] != self::MODULE_STATUS_ACTIVE) {

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get not validated custom module system requirements
     *
     * @param array $installConfig
     * @return array
     */
    public function getNotValidatedCustomModuleSystemRequirements(array $installConfig)
    {
        $notValidatedRequirements = [];
        $requirements = !empty($installConfig['system_requirements'])
            ? $installConfig['system_requirements']
            : null;

        // check php extensions
        if (!empty($requirements['php_extensions'])) {
            asort($requirements['php_extensions']);

            foreach ($requirements['php_extensions'] as $extension) {
                if (false === ($result = extension_loaded($extension))) {
                    $notValidatedRequirements['php_extensions'][] = [
                        'name' => $extension,
                        'current' => 'Not installed',
                        'desired' => 'Installed'
                    ];
                }
            }
        }

        // check php settings
        if (!empty($requirements['php_settings'])) {
            asort($requirements['php_settings']);

            foreach ($requirements['php_settings'] as $setting => $value) {
                if ($value != ($currentSettingValue = ini_get($setting))) {
                    $notValidatedRequirements['php_settings'][] = [
                        'name' => $setting,
                        'current' => $currentSettingValue,
                        'desired' => $value
                    ];
                }
            }
        }

        // check php disabled functions
        if (!empty($requirements['php_enabled_functions'])) {
            $disabledList = explode(',', ini_get('disable_functions'));
            asort($requirements['php_enabled_functions']);

            foreach ($requirements['php_enabled_functions'] as $function) {
                if (in_array($function, $disabledList)) {
                    $notValidatedRequirements['php_enabled_functions'][] = [
                        'name' => $function,
                        'current' => 'Disabled',
                        'desired' => 'Enabled'
                    ];
                }
            }
        }

        // check php version
        if (!empty($requirements['php_version'])) {
            if (version_compare(PHP_VERSION, $requirements['php_version']) == -1) {
                $notValidatedRequirements['php_version'][] = [
                    'name' => 'PHP',
                    'current' => PHP_VERSION,
                    'desired' => $requirements['php_version']
                ];
            }
        }

        return $notValidatedRequirements;
    }

    /**
     * Add custom module translations
     *
     * @var array $moduleConfig
     * @return void
     */
    protected function addCustomModuleTranslations(array $moduleConfig)
    {
        $translator = ServiceLocatorService::getServiceLocator()->get('translator');
        $translationPattern = !empty($moduleConfig['translator']['translation_file_patterns'])
            ? $moduleConfig['translator']['translation_file_patterns']
            : null;

        if ($translationPattern) {
            foreach ($translationPattern as $pattern) {
                $translator->addTranslationFilePattern($pattern['type'],
                        $pattern['base_dir'], $pattern['pattern'], $pattern['text_domain']);
            }
        }
    }    
}
<?php
namespace Application\Model;

use Application\Utility\ApplicationFtp as ApplicationFtpUtility;
use Application\Event\ApplicationEvent;
use Application\Exception\ApplicationException;
use Application\Service\Application as ApplicationService;
use Application\Utility\ApplicationFileSystem as ApplicationFileSystemUtility;
use Application\Utility\ApplicationCache as ApplicationCacheUtility;
use Layout\Utility\LayoutCache as LayoutCacheUtility;
use Localization\Utility\LocalizationCache as LocalizationCacheUtility;
use Page\Utility\PageCache as PageCacheUtility;
use User\Utility\UserCache as UserCacheUtility;
use XmlRpc\Utility\XmlRpcCache as XmlRpcCacheUtility;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Paginator\Paginator;
use DirectoryIterator;
use CallbackFilterIterator;
use Exception;

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
     * Get installed modules
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string status
     *      string type
     * @return object
     */
    public function getInstalledModules($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'type',
            'status',
            'version',
            'vendor',
            'email'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'application_module'])
            ->columns([
                'id',
                'name',
                'type',
                'status',
                'version',
                'vendor',
                'email' => 'vendor_email',
                'description'
            ])
            ->join(
                ['b' => 'application_module_depend'],
                'a.id = b.depend_module_id',
                [
                    'module_depends' => 'id'
                ],
                'left'
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case self::MODULE_STATUS_ACTIVE :
                case self::MODULE_STATUS_NOT_ACTIVE :
                    $select->where([
                        'a.status' => $filters['status']
                    ]);
                    break;

                default :
            }
        }

        // filter by type
        if (!empty($filters['type'])) {
            switch ($filters['type']) {
                case self::MODULE_TYPE_SYSTEM :
                case self::MODULE_TYPE_CUSTOM :
                    $select->where([
                        'a.type' => $filters['type']
                    ]);
                    break;

                default :
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        // load the custom deactivated module's translations
        foreach($paginator->getCurrentItems()->buffer() as $module) {
            if ($module['type'] == self::MODULE_TYPE_CUSTOM
                        && $module['status'] == self::MODULE_STATUS_NOT_ACTIVE) {

                $this->addCustomModuleTranslations($this->getCustomModuleConfig($module['name'], 'system', false));
            }
        }

        return $paginator;
    }

    /**
     * Is custom module
     *
     * @param string $module
     * @return boolean
     */
    public function isCustomModule($module)
    {
        $moduleDirectory = ApplicationService::getModulePath() . '/' . basename($module);
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
                return include ApplicationService::getModulePath() . '/' . basename($module) . $this->moduleInstallConfig;

            case 'system' :
            default :
                return include ApplicationService::getModulePath() . '/' . basename($module) . $this->moduleConfig;
        }
    }

    /**
     * Get all installed modules
     *
     * @return array
     */
    protected function getAllInstalledModules()
    {
        $select = $this->select();
        $select->from('application_module')
            ->columns([
                'id',
                'name'
            ])
        ->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $modulesList = [];
        foreach ($resultSet as $module) {
            $modulesList[$module->id] = $module->name;
        }

        return $modulesList;
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
        $installedModules = array_map('strtolower', $this->getAllInstalledModules());

        // get all directories and files
        $directoryIterator = new DirectoryIterator(ApplicationService::getModulePath());
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
                'vendor' => !empty($moduleInstallConfig['vendor']) ? $moduleInstallConfig['vendor'] : null,
                'email' => !empty($moduleInstallConfig['vendor_email']) ? $moduleInstallConfig['vendor_email'] : null,
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
                    'a.module_id = b.id',
                    [
                        'name',
                        'vendor',
                        'vendor_email',
                        'type',
                        'status'
                    ]
                )
                ->where(['depend_module_id' => $moduleInfo['id']]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            
            foreach ($resultSet as $module) {
                if ($module['type'] == self::MODULE_TYPE_CUSTOM
                        && $module['status'] == self::MODULE_STATUS_NOT_ACTIVE) {

                    $this->addCustomModuleTranslations($this->getCustomModuleConfig($module['name'], 'system', false));
                }

                $modules[] = [
                    'module' => $module['name'],
                    'vendor' => $module['vendor'],
                    'vendor_email' => $module['vendor_email']
                ];
            }
        }
        else {
            // try to get dependent modules from the not installed module
            if (false !== ($moduleInstallConfig = $this->getCustomModuleConfig($moduleName, 'install'))) {
                if (!empty($moduleInstallConfig['module_depends'])) {
                    foreach ($moduleInstallConfig['module_depends'] as $module) {
                        if (!isset($module['module'], $module['vendor'], $module['vendor_email'])) {
                            continue;
                        }

                        $moduleInfo = $this->getModuleInfo($module['module']);

                        if (!$moduleInfo
                                || strcasecmp($moduleInfo['vendor'], $module['vendor']) != 0
                                || strcasecmp($moduleInfo['vendor_email'], $module['vendor_email']) != 0
                                || $moduleInfo['status'] != self::MODULE_STATUS_ACTIVE) {

                            $modules[] = [
                                'module' => $module['module'],
                                'vendor' => $module['vendor'],
                                'vendor_email' => $module['vendor_email']
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
            if ($moduleInfo['type'] == self::MODULE_TYPE_CUSTOM
                    && $moduleInfo['status'] == self::MODULE_STATUS_NOT_ACTIVE) {

                $this->addCustomModuleTranslations($this->getCustomModuleConfig($moduleInfo['name'], 'system', false));
            }

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
     *      string version 
     *      string vendor 
     *      string vendor_email 
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          boolean is_public optional
     *      string install_sql optional
     *      string uninstall_sql optional
     *      array system_requirements optional
     *          array php_extensions optional
     *          array php_settings optional
     *          array php_enabled_functions optional
     *          array php_version optional
     *      string install_sql optional
     *      string install_intro optional
     *      string uninstall_sql optional
     *      string uninstall_intro optional
     *      string layout_path optional
     * @return boolean
     */
    public function checkCustomModuleDepends(array $installConfig)
    {
        if (!empty($installConfig['module_depends'])) {
            foreach ($installConfig['module_depends'] as $module) {
                if (!isset($module['module'], $module['vendor'], $module['vendor_email'])) {
                    continue;
                }

                $moduleInfo = $this->getModuleInfo($module['module']);

                if (!$moduleInfo
                        || strcasecmp($moduleInfo['vendor'], $module['vendor']) != 0
                        || strcasecmp($moduleInfo['vendor_email'], $module['vendor_email']) != 0
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
     *      string version 
     *      string vendor 
     *      string vendor_email 
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          boolean is_public optional
     *      string install_sql optional
     *      string uninstall_sql optional
     *      array system_requirements optional
     *          array php_extensions optional
     *          array php_settings optional
     *          array php_enabled_functions optional
     *          array php_version optional
     *      string install_sql optional
     *      string install_intro optional
     *      string uninstall_sql optional
     *      string uninstall_intro optional
     *      string layout_path optional
     * @return array
     */
    public function getNotValidatedCustomModuleSystemRequirements(array $installConfig)
    {
        $notValidatedRequirements = [];
        $requirements = !empty($installConfig['system_requirements'])
            ? $installConfig['system_requirements']
            : null;

        if (!$requirements) {
            return $notValidatedRequirements;
        }

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

        ApplicationCacheUtility::clearDynamicCache();
    }
    
    /**
     * Set custom module status
     *
     * @param string $moduleName
     * @param boolean $active
     * @return boolean|string
     */
    public function setCustomModuleStatus($moduleName, $active = true)
    {
        try {
            $update = $this->update()
                ->table('application_module')
                ->set([
                    'status' => $active ? self::MODULE_STATUS_ACTIVE : self::MODULE_STATUS_NOT_ACTIVE
                ])
                ->where([
                    'name' => $moduleName
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // regenerate list of custom active modules
            $this->generateCustomActiveModulesConfig();

            // clear caches
            $moduleInstallConfig = $this->getCustomModuleConfig($moduleName, 'install', false);
            $this->clearCaches((!empty($moduleInstallConfig['clear_caches']) ? $moduleInstallConfig['clear_caches'] : []));
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        true === $active
            ? ApplicationEvent::fireActivateCustomModuleEvent($moduleName)
            : ApplicationEvent::fireDeactivateCustomModuleEvent($moduleName);

        return true;
    }

    /**
     * Uninstall custom module
     *
     * @param string $moduleName
     * @param array $moduleInstallConfig
     *      string version 
     *      string vendor 
     *      string vendor_email 
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          boolean is_public optional
     *      string install_sql optional
     *      string uninstall_sql optional
     *      array system_requirements optional
     *          array php_extensions optional
     *          array php_settings optional
     *          array php_enabled_functions optional
     *          array php_version optional
     *      string install_sql optional
     *      string install_intro optional
     *      string uninstall_sql optional
     *      string uninstall_intro optional
     *      string layout_path optional
     * @return boolean|string
     */
    public function uninstallCustomModule($moduleName, array $moduleInstallConfig)
    {
        try {
            // clear caches
            $this->clearCaches((!empty($moduleInstallConfig['clear_caches']) ? $moduleInstallConfig['clear_caches'] : []));
            $this->addCustomModuleTranslations($this->getCustomModuleConfig($moduleName, 'system', false));

            $query = $this->delete('application_module')
                ->where([
                    'name' => $moduleName
                ]);

            $statement = $this->prepareStatementForSqlObject($query);
            $statement->execute();

            // regenerate list of custom active modules
            $this->generateCustomActiveModulesConfig();

            // execute an uninstall sql file
            if (!empty($moduleInstallConfig['uninstall_sql'])) {
                $this->executeSqlFile($moduleInstallConfig['uninstall_sql']);
            }

            // delete resources dirs
            if (!empty($moduleInstallConfig['resources'])) {
                foreach ($moduleInstallConfig['resources'] as $dir) {
                    if (!empty($dir['dir_name'])) {                      
                        $dirPath = ApplicationService::getResourcesDir() . $dir['dir_name'];

                        if (file_exists($dirPath)) {
                            if (true !== ($result = ApplicationFileSystemUtility::deleteFiles($dirPath, [], false, true))) {
                               throw new ApplicationException('Cannot delete the ' . $dirPath); 
                            }
                        }
                    }
                }
            }
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
            return $e->getMessage();
        }

        // fire the uninstall custom module event
        ApplicationEvent::fireUninstallCustomModuleEvent($moduleName);
        return true;
    }

    /**
     * Install custom module
     *
     * @param string $moduleName
     * @param array $moduleInstallConfig
     *      string version 
     *      string vendor 
     *      string vendor_email 
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          boolean is_public optional
     *      string install_sql optional
     *      string uninstall_sql optional
     *      array system_requirements optional
     *          array php_extensions optional
     *          array php_settings optional
     *          array php_enabled_functions optional
     *          array php_version optional
     *      string install_sql optional
     *      string install_intro optional
     *      string uninstall_sql optional
     *      string uninstall_intro optional
     *      string layout_path optional
     * @return boolean|string
     */
    public function installCustomModule($moduleName, array $moduleInstallConfig)
    {
        try {
            $version = !empty($moduleInstallConfig['version'])
                ? trim($moduleInstallConfig['version'])
                : null;

            $vendor = !empty($moduleInstallConfig['vendor'])
                ? trim($moduleInstallConfig['vendor'])
                : null;

            $vendorEmail = !empty($moduleInstallConfig['vendor_email'])
                ? trim($moduleInstallConfig['vendor_email'])
                : null;

            if (!$version || !$vendor || !$vendorEmail) {
                throw new ApplicationException('It is impossible to determine the module version, vendor or vendor email');
            }

            // clear caches
            $this->clearCaches((!empty($moduleInstallConfig['clear_caches']) ? $moduleInstallConfig['clear_caches'] : []));

            // add translations
            $this->addCustomModuleTranslations($this->getCustomModuleConfig($moduleName, 'system', false));

            $insert = $this->insert()
                ->into('application_module')
                ->values([
                    'name' => $moduleName,
                    'type' => self::MODULE_TYPE_CUSTOM,
                    'status' => self::MODULE_STATUS_ACTIVE,
                    'version' => $version,
                    'vendor' => $vendor,
                    'vendor_email' => $vendorEmail,
                    'description' => !empty($moduleInstallConfig['description']) ? $moduleInstallConfig['description'] : null,
                    'layout_path' => !empty($moduleInstallConfig['layout_path']) ? $moduleInstallConfig['layout_path'] : null
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // execute an install sql file
            if (!empty($moduleInstallConfig['install_sql'])) {
                $sqlFindKeys = [
                    '__module_id__'
                ];

                $sqlReplaysKeys = [
                    $insertId
                ];

                $this->executeSqlFile($moduleInstallConfig['install_sql'], [
                    'from' => $sqlFindKeys,
                    'to' => $sqlReplaysKeys
                ]);
            }

            // check the module's depends
            if (!empty($moduleInstallConfig['module_depends'])) {
                foreach ($moduleInstallConfig['module_depends'] as $module) {
                    if (!isset($module['module'], $module['vendor'], $module['vendor_email'])) {
                        continue;
                    }

                    if (null != ($moduleInfo = $this->getModuleInfo($module['module']))) {
                        $insert = $this->insert()
                            ->into('application_module_depend')
                            ->values([
                                'module_id' => $insertId,
                                'depend_module_id' => $moduleInfo['id']
                            ]);

                        $statement = $this->prepareStatementForSqlObject($insert);
                        $statement->execute();
                    }
                }
                
            }

            // create resources dirs
            if (!empty($moduleInstallConfig['resources'])) {
                $this->createResourceDirs($moduleInstallConfig['resources']);
            }

            // regenerate list of custom active modules
            $this->generateCustomActiveModulesConfig();
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);

            // delete module
            $query = $this->delete('application_module')
                ->where([
                    'name' => $moduleName
                ]);

            $statement = $this->prepareStatementForSqlObject($query);
            $statement->execute();

            return $e->getMessage();
        }

        // fire the install custom module event
        ApplicationEvent::fireInstallCustomModuleEvent($moduleName);
        return true;
    }

    /**
     * Create resources dirs
     *
     * @param array $resources
     *      string dir_name
     *      boolean is_public optional
     * @return void
     */
    protected function createResourceDirs(array $resources)
    {
        foreach ($resources as $dir) {
            if (!empty($dir['dir_name'])) {
                $dirPath = ApplicationService::getResourcesDir() . $dir['dir_name'];
                ApplicationFileSystemUtility::createDir($dirPath);

                if (isset($dir['is_public']) && false === $dir['is_public']) {
                    file_put_contents($dirPath . '/' . '.htaccess', 'Deny from all');
                }
            }
        }
    }

    /**
     * Upload module updates
     *
     * @param array $formData
     *      string login required
     *      string password required
     *      array module required
     * @param string $host
     * @return boolean|string
     */
    public function uploadModuleUpdates(array $formData, $host)
    {
        $uploadResult = true;

        try {
            // create a tmp dir
            $tmpDirName = $this->generateTmpDir();
            ApplicationFileSystemUtility::createDir($tmpDirName);

            // unzip a module updates into the tmp dir
            $this->unzipFiles($formData['module']['tmp_name'], $tmpDirName);

            // check the module's config
            if (!file_exists($tmpDirName . '/update_config.php')) {
                throw new ApplicationException('Cannot define the module\'s config file');
            }

            // get the module's config
            $updateModuleConfig = include $tmpDirName . '/update_config.php';

            $moduleName = !empty($updateModuleConfig['module']) ? $updateModuleConfig['module'] : null;
            $moduleVersion = !empty($updateModuleConfig['version']) ? $updateModuleConfig['version'] : null;
            $moduleVendor = !empty($updateModuleConfig['vendor']) ? $updateModuleConfig['vendor'] : null;
            $moduleVendorEmail = !empty($updateModuleConfig['vendor_email']) ? $updateModuleConfig['vendor_email'] : null;

            // check the module existing
            if (!$moduleName) {
                throw new ApplicationException('Module not found');
            }

            $moduleInstalled = true;

            // get module info from db
            if (null == ($moduleInfo = $this->getModuleInfo($moduleName))) {
                // get info from config
                if (false === ($moduleInfo = $this->getCustomModuleConfig($moduleName))) {
                    // nothing to update
                    throw new ApplicationException('Module not found');
                }

                $moduleInstalled = false;
            }

            // compare the modules options
            if (!$moduleVendor || !$moduleVendorEmail
                    || empty($moduleInfo['vendor']) || empty($moduleInfo['vendor_email'])
                    || strcasecmp($moduleVendor, $moduleInfo['vendor']) <> 0
                    || strcasecmp($moduleVendorEmail, $moduleInfo['vendor_email']) <> 0) {

                throw new ApplicationException('Module not found');
            }

            // compare the module versions
            if (!$moduleVersion
                    || empty($moduleInfo['version'])
                    || version_compare($moduleVersion, $moduleInfo['version']) <= 0) {

                throw new ApplicationException('This module updates are not necessary or not defined');
            }

            // get the module's path
            $modulePath   = !empty($updateModuleConfig['module_path'])
                ? $tmpDirName . '/' . $updateModuleConfig['module_path']
                : null;

            // check the module existing
            if ($modulePath && (!file_exists($modulePath) || !is_dir($modulePath))) {
                throw new ApplicationException('Cannot define the module\'s path into the config file');
            }

            // check the module layout existing
            $moduleLayoutPath = !empty($updateModuleConfig['layout_path']) && $moduleInfo['layout_path']
                ? $tmpDirName . '/' . $updateModuleConfig['layout_path']
                : null;

            if ($moduleLayoutPath && (!file_exists($moduleLayoutPath) || !is_dir($moduleLayoutPath))) {
                throw new ApplicationException('Cannot define the module\'s layout path into the config file');
            }

            // install updates
            if ($moduleInstalled) {
                // clear caches
                $this->clearCaches((!empty($updateModuleConfig['clear_caches']) ? $updateModuleConfig['clear_caches'] : []));

                // create resources dirs
                if (!empty($updateModuleConfig['create_resources'])) {
                    $this->createResourceDirs($updateModuleConfig['create_resources']);
                }

                // execute an update sql file
                if (!empty($updateModuleConfig['update_sql'])) {
                    $this->executeSqlFile($tmpDirName . '/' . $updateModuleConfig['update_sql']);
                }

                // delete unnecessary resources dirs
                if (!empty($updateModuleConfig['delete_resources'])) {
                    foreach ($updateModuleConfig['delete_resources'] as $dir) {
                        if (!empty($dir['dir_name'])) {
                            if (file_exists(ApplicationService::getResourcesDir() . $dir['dir_name'])) {
                                ApplicationFileSystemUtility::
                                        deleteFiles(ApplicationService::getResourcesDir() . $dir['dir_name'], [], false, true);
                            }
                        }
                    }
                }
            }

            // update files via FTP
            if ($modulePath || ($moduleLayoutPath && $moduleInfo['layout_path'])) {
                $ftp = new ApplicationFtpUtility($host, $formData['login'], $formData['password']);

                if ($modulePath) {
                    $ftp->copyDirectory($modulePath, ApplicationService::getModulePath(false) . '/' . $moduleName);
                }

                if ($moduleLayoutPath && $moduleInfo['layout_path']) {
                    $ftp->copyDirectory($moduleLayoutPath, basename(APPLICATION_PUBLIC)
                            . '/' . ApplicationService::getBaseLayoutPath(false) . '/' . $moduleInfo['layout_path']);
                }
            }

            if ($moduleInstalled) {
                // update version
                $update = $this->update()
                    ->table('application_module')
                    ->set([
                        'version' => $moduleVersion
                    ])
                    ->where([
                        'name' => $moduleName
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }
        }
        catch (Exception $e) {            
            ApplicationErrorLogger::log($e);
            $uploadResult = $e->getMessage();
        }

        // remove the tmp dir
        if (file_exists($tmpDirName)) {
            ApplicationFileSystemUtility::deleteFiles($tmpDirName, [], false, true);
        }

        // fire the upload module updates event
        if (true === $uploadResult) {
            ApplicationEvent::fireUploadModuleUpdatesEvent($moduleName);
        }

        return $uploadResult;
    }

    /**
     * Upload custom module
     *
     * @param array $formData
     *      string login required
     *      string password required
     *      array module required
     * @param string $host
     * @return boolean|string
     */
    public function uploadCustomModule(array $formData, $host)
    {
        $uploadResult = true;

        try {
            // create a tmp dir
            $tmpDirName = $this->generateTmpDir();
            ApplicationFileSystemUtility::createDir($tmpDirName);

            // unzip a custom module into the tmp dir
            $this->unzipFiles($formData['module']['tmp_name'], $tmpDirName);

            // check the module's config
            if (!file_exists($tmpDirName . '/config.php')) {
                throw new ApplicationException('Cannot define the module\'s config file');
            }

            // get the module's config
            $moduleConfig = include $tmpDirName . '/config.php';

            // get the module's path
            $modulePath   = !empty($moduleConfig['module_path'])
                ? $tmpDirName . '/' . $moduleConfig['module_path']
                : null;

            // check the module existing
            if (!$modulePath || !file_exists($modulePath) || !is_dir($modulePath)) {
                throw new ApplicationException('Cannot define the module\'s path into the config file');
            }

            if (!file_exists($modulePath . '/' . $this->moduleInstallConfig)
                    || !file_exists($modulePath . '/' . $this->moduleConfig)) {

                throw new ApplicationException('Module not found');
            }

            // check the module layout existing
            $moduleLayoutPath = null;
            if (!empty($moduleConfig['layout_path'])) {
                $moduleLayoutPath = $tmpDirName . '/' . $moduleConfig['layout_path'];

                if (!file_exists($moduleLayoutPath) || !is_dir($moduleLayoutPath)) {
                    throw new ApplicationException('Cannot define the module\'s layout path into the config file');
                }
            }

            // check the module existing into modules and layouts dirs
            $moduleName = basename($modulePath);
            $globalModulePath = ApplicationService::getModulePath(false) . '/' . $moduleName;

            $moduleLayoutName = $moduleLayoutPath
                ? basename($moduleLayoutPath)
                : null;

            $localModulePath = APPLICATION_ROOT . '/' . $globalModulePath;
            $localModuleLayout = $moduleLayoutName
                ? ApplicationService::getBaseLayoutPath() . '/' . $moduleLayoutName
                : null;

            if (file_exists($localModulePath)
                    || ($localModuleLayout && file_exists($localModuleLayout))) {

                throw new ApplicationException('Module already uploaded');
            }

            // upload the module via FTP 
            $ftp = new ApplicationFtpUtility($host, $formData['login'], $formData['password']);
            $ftp->createDirectory($globalModulePath);
            $ftp->copyDirectory($modulePath, $globalModulePath);

            // upload the module's layout
            if ($moduleLayoutPath) {
                $globalModuleLayoutPath = basename(APPLICATION_PUBLIC) .
                        '/' . ApplicationService::getBaseLayoutPath(false) . '/' . $moduleLayoutName;

                $ftp->createDirectory($globalModuleLayoutPath);
                $ftp->copyDirectory($moduleLayoutPath, $globalModuleLayoutPath);
            }
        }
        catch (Exception $e) {            
            ApplicationErrorLogger::log($e);
            $uploadResult = $e->getMessage();
        }

        // remove the tmp dir
        if (file_exists($tmpDirName)) {
            ApplicationFileSystemUtility::deleteFiles($tmpDirName, [], false, true);
        }

        // fire the upload custom module event
        if (true === $uploadResult) {
            ApplicationEvent::fireUploadCustomModuleEvent($moduleName);
        }

        return $uploadResult;
    }

    /**
     * Check modules structure pages
     *
     * @param string $module
     * @return boolean
     */
    public function checkModuleStructurePages($module)
    {
        $select = $this->select();
        $select->from(['a' => 'application_module'])
            ->columns([])
            ->join(
                ['b' => 'page_system'],
                'a.id = b.module',
                []
            )
            ->join(
                ['c' => 'page_structure'],
                'b.id = c.system_page',
                [
                    'id'
                ]
            )
            ->where([
                'name' => $module
            ])
            ->limit(1);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $result =  $resultSet->count() ? true : false;

        // load the module's translations
        if (true === $result) {
            $module = $this->getModuleInfo($module);
            if ($module['type'] == self::MODULE_TYPE_CUSTOM
                        && $module['status'] == self::MODULE_STATUS_NOT_ACTIVE) {

                $this->addCustomModuleTranslations($this->getCustomModuleConfig($module['name'], 'system', false));
            }
        }

        return $result;
    }

    /**
     * Generate custom active modules config
     *
     * @return void
     */
    protected function generateCustomActiveModulesConfig()
    {
        // get list of custom modules
        $select = $this->select();
        $select->from('application_module')
            ->columns([
                'name'
            ])
            ->where([
                'type' => self::MODULE_TYPE_CUSTOM,
                'status' => self::MODULE_STATUS_ACTIVE
            ])
            ->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $modules = null;
        foreach($resultSet as $module) {
            $modules .= "'" . $module->name . "',";
        }

        file_put_contents(ApplicationService::getCustomModuleConfig(), '<?php return ['. rtrim($modules, ',') . '];');
    }

    /**
     * Clear caches
     *
     * @param array $caches
     *      boolean setting optional
     *      boolean time_zone optional
     *      boolean admin_menu optional
     *      boolean js_cache optional
     *      boolean css_cache optional
     *      boolean layout optional
     *      boolean localization optional
     *      boolean page optional
     *      boolean user optional
     *      boloean xmlrpc optional
     * @return void
     */
    protected function clearCaches(array $caches = [])
    {
        // clear the modules and system config caches
        ApplicationCacheUtility::clearModuleCache();
        ApplicationCacheUtility::clearConfigCache();
        ApplicationCacheUtility::clearDynamicCache();

        foreach ($caches as $cacheName => $clear) {
            if (false === (bool) $clear) {
                continue;
            }

            switch ($cacheName) {
                case 'setting' :
                    ApplicationCacheUtility::clearSettingCache();
                    break;

                case 'time_zone' :
                    ApplicationCacheUtility::clearTimeZoneCache();
                    break;

                case 'admin_menu' :
                    ApplicationCacheUtility::clearAdminMenuCache();
                    break;

                case 'js_cache' :
                    ApplicationCacheUtility::clearJsCache();
                    break;

                case 'css_cache' :
                    ApplicationCacheUtility::clearCssCache();
                    break;

                case 'layout' :
                    LayoutCacheUtility::clearLayoutCache();
                    break;

                case 'localization' :
                    LocalizationCacheUtility::clearLocalizationCache();
                    break;

                case 'page' :
                    PageCacheUtility::clearPageCache();
                    break;

                case 'user' :
                    UserCacheUtility::clearUserCache();
                    break;

                case 'xmlrpc' :
                    XmlRpcCacheUtility::clearXmlRpcCache();
                    break;
            }
        }
    }
}
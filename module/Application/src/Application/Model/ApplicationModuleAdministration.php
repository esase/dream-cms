<?php
namespace Application\Model;

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
use Zend\Db\Adapter\Adapter as DbAdapter;
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
                    'a.depend_module_id = b.id',
                    [
                        'name',
                        'vendor',
                        'vendor_email'
                    ]
                )
                ->where(['module_id' => $moduleInfo['id']]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            
            foreach ($resultSet as $module) {
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
     *      string version optional
     *      string vendor optional
     *      string vendor_email optional
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          bolean is_public optional
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
     *      string version optional
     *      string vendor optional
     *      string vendor_email optional
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          bolean is_public optional
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
    }

    /**
     * Install custom module
     *
     * @param string $moduleName
     * @param array $moduleInstallConfig
     *      string version optional
     *      string vendor optional
     *      string vendor_email optional
     *      string description optional
     *      array module_depends optional
     *          string module
     *          string vendor
     *          string vendor_email
     *      array clear_caches optional
     *      array resources optional
     *          string dir_name
     *          bolean is_public optional
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
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // 1. Add info about this module into the `application_module` +
            // 2. Add module's depends +
            // 3. Clear modules cache +
            // 4 Clear other caches +
            // 5. Resources dir +
            // 6. Regenerate the /var/www/dream_cms/config/module/custom.php +
            // 7. Execute install sql and pass into this file the module insert id +
            // 9. TEST IN IN PRODACTION +
            // 10. Test pages and widgets +
            //      a. When page is inactive the still into menu +
            // 11. Clear user cache when hi deleted +
            // 12. Unit Tests +
            // 13. XmlRpc +
            // 15. Everethink should be hidden when module is not active +
            // 18. Test also xmlsite map for innaactive modules +
            // 19. Think about global deny all inactive modules (Maybe delete them from custom config ?) +
            // 17. XmlRps classes should work with only active modules +
            // 20. An space brake the xml map here - http://localhost/dream_cms/public/sitemap.xml (I think it somewhre in modules.php) +
            // 25. Fix tinymce and fix files urls in FileManager +
            // 22. Check all layout url in images, js, photos, etc +
            // 21. Fix all layout troubles (check it also with different layout and enabled/disabled cache) +
            // 24. Check again whole system and modules installation for both mode (as local dir and domain) +
            // 26. Fron-end layout has en empty in the start of file +
            // 23. Rename back the www dir to public +

            // 16. Widget sorting wirking wrong when some widgets are inactive !!!!!!!!!!!
            // 8. SYSTEM EVENT
            // 25. Don't show widget if there are not any dependent system pages
            

            $insert = $this->insert()
                ->into('application_module')
                ->values([
                    'name' => $moduleName,
                    'type' => self::MODULE_TYPE_CUSTOM,
                    'status' => self::MODULE_STATUS_ACTIVE,
                    'version' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['version'] : null,
                    'vendor' => !empty($moduleInstallConfig['vendor']) ? $moduleInstallConfig['vendor'] : null,
                    'vendor_email' => !empty($moduleInstallConfig['vendor_email']) ? $moduleInstallConfig['vendor_email'] : null,
                    'description' => !empty($moduleInstallConfig['description']) ? $moduleInstallConfig['description'] : null
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
            
            // regenerate list of custom active modules
            $this->generateCustomActiveModulesConfig();

            // create resources dirs
            if (!empty($moduleInstallConfig['resources'])) {
                foreach ($moduleInstallConfig['resources'] as $dir) {
                    if (!empty($dir['dir_name'])) {
                        $dirPath = ApplicationService::getResourcesDir() . $dir['dir_name'];
                        ApplicationFileSystemUtility::createDir($dirPath);

                        if (isset($dir['is_public']) && false === $dir['is_public']) {
                            file_put_contents($dirPath . '/' . '.htaccess', 'Deny from all');
                        }
                    }
                }
            }

            // clear caches
            $this->clearCaches((!empty($moduleInstallConfig['clear_caches']) ? $moduleInstallConfig['clear_caches'] : []));

            // add translations
            $this->addCustomModuleTranslations($this->getCustomModuleConfig($moduleName, 'system', false));
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
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
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $modules = null;
        foreach($resultSet as $module) {
            $modules .= "'" . $module->name . "',";
        }

        file_put_contents(APPLICATION_ROOT .
                '/config/module/custom.php', '<?php return ['. rtrim($modules, ',') . '];');
    }

    /**
     * Execute sql file
     *
     * @param string $filePath
     * @param array $replace
     *      string from
     *      string to
     * @throws ApplicationException
     * @return void
     */
    protected function executeSqlFile($filePath, array $replace = [])
    {
        if(!file_exists($filePath) || !($handler = fopen($filePath, 'r'))) {
            throw new ApplicationException('Sql file not found or permission denied');
        }

        $query = null;
        $delimiter = ';';
        $result = [];

        // collect all queries
        while(!feof($handler)) {
            $str = trim(fgets($handler));

            if(empty($str) || $str[0] == '' || $str[0] == '#' || ($str[0] == '-' && $str[1] == '-'))
                continue;

            // change delimiter
            if(strpos($str, 'DELIMITER //') !== false || strpos($str, 'DELIMITER ;') !== false) {
                $delimiter = trim(str_replace('DELIMITER', '', $str));
                continue;
            }

            $query .= ' ' . $str;

            // check for multiline query
            if(substr($str, -strlen($delimiter)) != $delimiter) {
                continue;
            }

            // execute query
            if (!empty($replace['from']) && !empty($replace['to'])) {
                $query = str_replace($replace['from'], $replace['to'], $query);
            }

            if($delimiter != ';') {
                $query = str_replace($delimiter, '', $query);
            }

            $this->adapter->query(trim($query), DbAdapter::QUERY_MODE_EXECUTE);
            $query = null;
        }

        fclose($handler);
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